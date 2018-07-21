<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/ImportDocs/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/importdocs')) {
            $cache->deleteTree(
                $dev . 'assets/components/importdocs/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/importdocs/', $dev . 'assets/components/importdocs');
        }
        if (!is_link($dev . 'core/components/importdocs')) {
            $cache->deleteTree(
                $dev . 'core/components/importdocs/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/importdocs/', $dev . 'core/components/importdocs');
        }
    }
}

return true;