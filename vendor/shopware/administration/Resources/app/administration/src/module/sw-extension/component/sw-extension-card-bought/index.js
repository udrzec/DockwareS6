import template from './sw-extension-card-bought.html.twig';
import './sw-extension-card-bought.scss';

const { Component } = Shopware;

/**
 * @private
 */
Component.extend('sw-extension-card-bought', 'sw-extension-card-base', {
    template,

    mixins: ['sw-extension-error'],

    props: {
        extension: {
            type: Object,
            required: false,
            default: null
        }
    },

    data() {
        return {
            showDeactivationModal: false,
            showRatingModal: false,
            showExtensionInstallationFailedModal: false
        };
    },

    computed: {
        extensionCardClasses() {
            return {
                'sw-extension-card-bought': true,
                ...this.$super('extensionCardClasses')
            };
        },

        priceInfo() {
            return this.extension?.storeLicense?.paymentText ?? '';
        },

        detailLink() {
            return {
                name: 'sw.extension.store.detail',
                params: {
                    id: String(this.extension.storeExtension ? this.extension.storeExtension.id : this.extension.id)
                }
            };
        },

        subscriptionExpiredText() {
            const expirationDate = this.extension?.storeLicense?.expirationDate ?? null;

            if (expirationDate === null) {
                return null;
            }

            const localDateString = (new Date(expirationDate)).toLocaleDateString();

            return this.$t(
                'sw-extension-store.component.sw-extension-card-bought.subscriptionExpiredAt',
                { date: localDateString }
            );
        }
    },

    methods: {
        async changeExtensionStatus() {
            // State in checkbox has already changed so we have to treat extension.active as the state to change to
            if (this.isActive) {
                await this.activateExtension();
                return;
            }

            if (!this.license
                || this.license.variant !== this.shopwareExtensionService.EXTENSION_VARIANT_TYPES.RENT) {
                await this.deactivateExtension();
                return;
            }

            this.showDeactivationModal = true;

            // reset state in next tick to not visually reset state until async operations finish
            this.$nextTick(() => {
                this.extension.active = !this.extension.active;
            });
        },

        async activateExtension() {
            try {
                this.isLoading = true;

                await this.shopwareExtensionService.activateExtension(
                    this.extension.name,
                    this.extension.type
                );
                this.extension.active = true;
                this.clearCacheAndReloadPage();
            } catch (e) {
                this.extension.active = false;
                this.showExtensionErrors(e);
            } finally {
                this.isLoading = false;
            }
        },

        async deactivateExtension() {
            try {
                this.isLoading = true;

                await this.shopwareExtensionService.deactivateExtension(
                    this.extension.name,
                    this.extension.type
                );
                this.extension.active = false;
                this.clearCacheAndReloadPage();
            } catch (e) {
                this.extension.active = true;
                this.showExtensionErrors(e);
            } finally {
                this.isLoading = false;
            }
        },

        closeDeactivationModal() {
            this.showDeactivationModal = false;
        },

        async closeModalAndDeactivateExtension() {
            await this.deactivateExtension();
            this.showDeactivationModal = false;
        },

        async installExtension() {
            this.isLoading = true;

            try {
                if (this.extension.source === 'store') {
                    await this.extensionStoreActionService.downloadExtension(
                        this.extension.name
                    );
                }

                await this.shopwareExtensionService.installExtension(
                    this.extension.name,
                    this.extension.type
                );
                await this.clearCacheAndReloadPage();
            } catch (e) {
                this.showExtensionErrors(e);
                this.showExtensionInstallationFailedModal = true;
            } finally {
                this.isLoading = false;
            }
        },

        async cancelAndRemoveExtension() {
            try {
                this.isLoading = true;

                await this.shopwareExtensionService.cancelLicense(this.extension.storeLicense.id);
                await this.shopwareExtensionService.removeExtension(
                    this.extension.name,
                    this.extension.type
                );
                this.closeRemovalModal();

                this.$nextTick(() => {
                    this.emitUpdateList();
                });
            } catch (e) {
                this.showExtensionErrors(e);
            } finally {
                this.isLoading = false;
            }
        },

        openRatingModal() {
            this.showRatingModal = true;
        },

        closeRatingModal() {
            this.showRatingModal = false;
        },

        closeInstallationFailedNotification() {
            this.showExtensionInstallationFailedModal = false;
        }
    }
});
