<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\Container073HdYk\Shopware_Production_KernelDevDebugContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/Container073HdYk/Shopware_Production_KernelDevDebugContainer.php') {
    touch(__DIR__.'/Container073HdYk.legacy');

    return;
}

if (!\class_exists(Shopware_Production_KernelDevDebugContainer::class, false)) {
    \class_alias(\Container073HdYk\Shopware_Production_KernelDevDebugContainer::class, Shopware_Production_KernelDevDebugContainer::class, false);
}

return new \Container073HdYk\Shopware_Production_KernelDevDebugContainer([
    'container.build_hash' => '073HdYk',
    'container.build_id' => '50789961',
    'container.build_time' => 1623220652,
], __DIR__.\DIRECTORY_SEPARATOR.'Container073HdYk');
