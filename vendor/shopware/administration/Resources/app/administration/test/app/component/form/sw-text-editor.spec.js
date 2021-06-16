import { shallowMount, createLocalVue } from '@vue/test-utils';
import 'src/app/component/form/sw-text-editor';
import 'src/app/component/form/sw-text-editor/sw-text-editor-toolbar';
import 'src/app/component/form/sw-text-editor/sw-text-editor-toolbar-button';
import 'src/app/component/form/sw-field';
import 'src/app/component/form/sw-text-field';
import 'src/app/component/form/field-base/sw-contextual-field';
import 'src/app/component/form/field-base/sw-block-field';
import 'src/app/component/form/field-base/sw-base-field';
import 'src/app/component/form/sw-colorpicker';
import 'src/app/component/form/sw-compact-colorpicker';
import 'src/app/component/form/sw-switch-field';
import 'src/app/component/form/sw-checkbox-field';
import 'src/app/component/base/sw-container';
import 'src/app/component/base/sw-button';

function createWrapper(allowInlineDataMapping = true) {
    // set body for app
    document.body.innerHTML = '<div id="app"></div>';

    const localVue = createLocalVue();
    localVue.directive('tooltip', {});

    return shallowMount(Shopware.Component.build('sw-text-editor'), {
        attachTo: document.getElementById('app'),
        propsData: {
            allowInlineDataMapping
        },
        localVue,
        stubs: {
            'sw-text-editor-toolbar': Shopware.Component.build('sw-text-editor-toolbar'),
            'sw-text-editor-toolbar-button': Shopware.Component.build('sw-text-editor-toolbar-button'),
            'sw-icon': { template: '<div class="sw-icon"></div>' },
            'sw-field': Shopware.Component.build('sw-field'),
            'sw-text-field': Shopware.Component.build('sw-text-field'),
            'sw-contextual-field': Shopware.Component.build('sw-contextual-field'),
            'sw-block-field': Shopware.Component.build('sw-block-field'),
            'sw-base-field': Shopware.Component.build('sw-base-field'),
            'sw-checkbox-field': Shopware.Component.build('sw-checkbox-field'),
            'sw-switch-field': Shopware.Component.build('sw-switch-field'),
            'sw-field-error': true,
            'sw-compact-colorpicker': Shopware.Component.build('sw-compact-colorpicker'),
            'sw-colorpicker': Shopware.Component.build('sw-colorpicker'),
            'sw-container': Shopware.Component.build('sw-container'),
            'sw-button': Shopware.Component.build('sw-button')
        },
        data() {
            return {
                cmsPageState: {
                    currentMappingTypes: {
                        string: [
                            'category.type'
                        ]
                    }
                }
            };
        },
        provide: {
            validationService: {}
        }
    });
}

async function addTextToEditor(wrapper, content) {
    const contentEditor = wrapper.find('.sw-text-editor__content-editor');

    // click in editable content
    await wrapper.trigger('click');

    // write something in the editor
    contentEditor.element.innerHTML = content;

    await contentEditor.trigger('input');
}

async function addAndCheckSelection(wrapper, element, start, end, text) {
    // set cursor range
    const range = document.createRange();
    range.setStart(element.firstChild, start);
    range.setEnd(element.firstChild, end);

    // check if range selects "foo-bar"
    expect(range.toString()).toBe(text);

    // check if nothing was selected
    expect(wrapper.vm.selection).toBe(null);

    // add range to selection
    const selection = document.getSelection();
    selection.addRange(range);

    // check if range and selection fits
    expect(range.toString()).toEqual(text);
    expect(selection.toString()).toEqual(text);
    expect(selection.rangeCount).toEqual(1);

    // add mouseup event to get selection
    document.dispatchEvent(new Event('mouseup'));
    await wrapper.vm.$nextTick();

    // check if selection was set
    expect(wrapper.vm.selection).toBe(selection);
}

describe('src/app/component/form/sw-text-editor', () => {
    /** @type Wrapper */
    let wrapper;

    beforeAll(() => {
        // 'Implement' innerText in JSDOM: https://github.com/jsdom/jsdom/issues/1245
        Object.defineProperty(global.Element.prototype, 'innerText', {
            get() {
                return this.textContent;
            }
        });

        // implement execCommand mock
        document.execCommand = (command, ui, value) => {
            const range = document.getSelection().getRangeAt(0);
            if (!range) return;

            if (command === 'insertHTML') {
                const newNode = document.createElement('template');
                newNode.innerHTML = value.trim();

                range.deleteContents();
                range.insertNode(newNode.content.firstChild);
            }

            if (command === 'insertText') {
                const newTextNode = document.createTextNode(value.trim());

                range.deleteContents();
                range.insertNode(newTextNode);
            }
        };
    });

    beforeEach(() => {});

    afterEach(() => {
        if (wrapper) { wrapper.destroy(); }
        document.getSelection().removeAllRanges();
    });

    it('should be a Vue.js component', async () => {
        wrapper = await createWrapper();
        expect(wrapper.vm).toBeTruthy();
    });

    it('should toggle placeholder', async () => {
        wrapper = await createWrapper();
        const placeholder = 'Enter description...';
        await wrapper.setProps({ placeholder: placeholder });

        // replace placeholder with value
        const editorPlaceholder = wrapper.find('.sw-text-editor__content .sw-text-editor__content-placeholder');
        expect(editorPlaceholder.element.innerText).toEqual(placeholder);

        const content = wrapper.find('.sw-text-editor__content .sw-text-editor__content-editor');
        const expectedValue = 'I am not the Placeholder';
        await wrapper.setProps({ value: expectedValue });

        expect(content.element.innerText).toEqual(expectedValue);
        expect(wrapper.find('.sw-text-editor__content .sw-text-editor__content-placeholder').exists()).toBeFalsy();

        // replace value with placeholder
        await wrapper.setProps({ value: null });
        expect(wrapper.find('.sw-text-editor__content .sw-text-editor__content-placeholder').exists()).toBeTruthy();
        expect(editorPlaceholder.element.innerText).toEqual(placeholder);
        expect(content.element.innerText).toEqual('');
    });

    it('should insert the link correctly', async () => {
        wrapper = await createWrapper();
        const contentEditor = wrapper.find('.sw-text-editor__content-editor');
        const buttonLink = wrapper.find('.sw-text-editor-toolbar-button__type-link');

        await addTextToEditor(wrapper, '<p id="fooBarTest">Go to foo-bar</p>');

        const paragraph = document.getElementById('fooBarTest');
        await addAndCheckSelection(wrapper, paragraph, 6, 13, 'foo-bar');

        // click on button for link generation
        await buttonLink.find('.sw-text-editor-toolbar-button__icon').trigger('click');

        // set link
        await buttonLink.find('#sw-field--buttonConfig-value').setValue('https://www.foo-bar.com');
        await buttonLink.find('#sw-field--buttonConfig-value').trigger('change');

        // set new tab
        buttonLink.find('input[name="sw-field--buttonConfig-newTab"]').element.checked = true;
        await buttonLink.find('input[name="sw-field--buttonConfig-newTab"]').trigger('change');

        // insert link
        await buttonLink.find('.sw-text-editor-toolbar-button__link-menu-buttons-button-insert').trigger('click');
        await wrapper.vm.$nextTick();

        // eslint-disable-next-line max-len
        const expectedValue = '<p id="fooBarTest">Go to <a target="_blank" href="https://www.foo-bar.com" rel="noopener">foo-bar</a></p>';

        // check if link was insert right in content editor
        expect(contentEditor.element.innerHTML).toEqual(expectedValue);

        // check if content value was emitted right
        const emittedValue = wrapper.emitted().input[0];
        expect(emittedValue[0]).toEqual(expectedValue);
    });

    it('should handle inserting inline mapping', async () => {
        wrapper = createWrapper();

        const contentEditor = wrapper.find('.sw-text-editor__content-editor');

        await addTextToEditor(wrapper, '<p id="text-editor-content">some random text</p>');
        const paragraph = document.getElementById('text-editor-content');

        await addAndCheckSelection(wrapper, paragraph, 12, 16, 'text');

        // eslint-disable-next-line max-len
        const inlineMappingButton = wrapper.find('.sw-text-editor-toolbar-button__type-data-mapping .sw-text-editor-toolbar-button__icon');
        await inlineMappingButton.trigger('click');

        await wrapper.vm.$nextTick();

        // insert inline data mapping
        wrapper.find('.sw-text-editor-toolbar-button__children :first-child > div').trigger('click');
        await wrapper.vm.$nextTick();

        // check if newly edited content is correct
        const expectedTextContent = '<p id="text-editor-content">some random {{ category.type }}</p>';
        expect(contentEditor.element.innerHTML).toBe(expectedTextContent);

        // check emitted events
        const event = wrapper.emitted('input')[0];
        expect(event[0]).toBe(expectedTextContent);
    });

    it('should return true if selection contains one or two opening brackets', () => {
        wrapper = createWrapper();

        const containsOneBracket = wrapper.vm.containsStartBracket('{');
        expect(containsOneBracket).toBe(true);

        const containsTwoBrackets = wrapper.vm.containsStartBracket('{{');
        expect(containsTwoBrackets).toBe(true);
    });

    it('should return false if selection contains no opening brackets', () => {
        wrapper = createWrapper();

        const containsStartBracket = wrapper.vm.containsStartBracket('no start bracket');
        expect(containsStartBracket).toBe(false);
    });

    it('should return true if selection contains one or two closing brackets', () => {
        wrapper = createWrapper();

        const containsOneBracket = wrapper.vm.containsEndBracket('}');
        expect(containsOneBracket).toBe(true);

        const containsTwoBrackets = wrapper.vm.containsEndBracket('}}');
        expect(containsTwoBrackets).toBe(true);
    });

    it('should return false if selection contains no closing brackets', () => {
        wrapper = createWrapper();

        const containsEndBracket = wrapper.vm.containsStartBracket('no start bracket');
        expect(containsEndBracket).toBe(false);
    });

    it('should return true if selection is inside inline mapping', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ category.name }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 3, 11, 'category');

        const isInsideInlineMapping = wrapper.vm.isInsideInlineMapping();
        expect(isInsideInlineMapping).toBe(true);
    });

    it('should return true if selection is inside inline mapping with mapping around', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">Before {{ test }} {{ category.name }} {{ example }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 21, 29, 'category');

        const isInsideInlineMapping = wrapper.vm.isInsideInlineMapping();
        expect(isInsideInlineMapping).toBe(true);
    });

    it('should return false if selection is not inside inline mapping with mapping around', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">Some text before {{ test }} category.name }} {{ example }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 28, 36, 'category');

        const isInsideInlineMapping = wrapper.vm.isInsideInlineMapping();
        expect(isInsideInlineMapping).toBe(false);
    });

    it('should return false if selection is not inside inline mapping', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">not inside inline mapping</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 4, 10, 'inside');

        const isInsideInlineMapping = wrapper.vm.isInsideInlineMapping();
        expect(isInsideInlineMapping).toBe(false);
    });

    it('should return false if selection is not inside inline mapping with mappings around', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ example }} not inside mapping {{ example }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 18, 24, 'inside');

        const isInsideInlineMapping = wrapper.vm.isInsideInlineMapping();
        expect(isInsideInlineMapping).toBe(false);
    });

    it('should expand selection to nearest closing bracket', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ category.name }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 0, 5, '{{ ca');

        wrapper.vm.expandSelectionToNearestEndBracket();

        const expandedSelection = document.getSelection();
        expect(expandedSelection.toString()).toBe('{{ category.name }}');
    });

    it('should expand selection to nearest opening bracket', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ category.name }}</p>');

        const paragraph = document.getElementById('paragraph');
        await addAndCheckSelection(wrapper, paragraph, 15, 19, 'e }}');

        wrapper.vm.expandSelectionToNearestStartBracket();

        const expandedSelection = document.getSelection();
        expect(expandedSelection.toString()).toBe('{{ category.name }}');
    });

    it('should set the selection correctly when using the setSelection method', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">random text</p>');
        const paragraph = document.getElementById('paragraph');

        // add empty selection
        wrapper.vm.selection = document.getSelection();

        // check that nothing is selected
        const emptySelection = document.getSelection().toString();
        expect(emptySelection).toBe('');

        // add selection
        wrapper.vm.setSelection(paragraph.firstChild, paragraph.firstChild, 3, 6);

        const newSelection = document.getSelection().toString();
        expect(newSelection).toBe('dom');
    });

    it('should expand selection one to the left if only one opening bracket is selected', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ category.name }}</p>');
        const paragraph = document.getElementById('paragraph');

        await addAndCheckSelection(wrapper, paragraph, 1, 4, '{ c');

        wrapper.vm.expandSelectionToNearestEndBracket();

        const newSelection = document.getSelection().toString();
        expect(newSelection).toBe('{{ category.name }}');
    });

    it('should expand selection one to the right if only one closing bracket is selected', async () => {
        wrapper = createWrapper();

        await addTextToEditor(wrapper, '<p id="paragraph">{{ category.name }}</p>');
        const paragraph = document.getElementById('paragraph');

        await addAndCheckSelection(wrapper, paragraph, 15, 18, 'e }');

        wrapper.vm.expandSelectionToNearestStartBracket();

        const newSelection = document.getSelection().toString();
        expect(newSelection).toBe('{{ category.name }}');
    });

    it('should not show the inline mapping button when prop does not allow it to', () => {
        wrapper = createWrapper(false);
        const inlineMappingButton = wrapper.find('.sw-text-editor-toolbar-button__type-data-mapping');

        expect(inlineMappingButton.exists()).toBe(false);
    });
});
