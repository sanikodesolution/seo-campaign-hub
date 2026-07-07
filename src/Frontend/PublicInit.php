<?php
/**
 * Frontend Initialization
 *
 * @package SEO_Campaign_Hub\Frontend
 */

namespace SEO_Campaign_Hub\Frontend;

use SEO_Campaign_Hub\Core\Container;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PublicInit
 *
 * Boots frontend-only behavior.
 */
class PublicInit {

    /**
     * Container instance.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Constructor.
     *
     * @param Container $container Plugin DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Initialize frontend hooks.
     *
     * @return void
     */
    public function init(): void {
        // Intentionally minimal: core frontend hooks are registered in Core\Plugin.
        do_action( 'seo_campaign_hub_frontend_ready', $this->container );
    }
}
