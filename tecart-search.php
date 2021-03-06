<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Data;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use RocketTheme\Toolbox\Event\Event;

use Grav\Plugin\TecartSearch\Classes\SearchIndexer\SearchIndexer;
use Grav\Plugin\TecartSearch\Classes\BlueprintHelper\BlueprintHelper;

require_once __DIR__ . '/classes/SearchIndexer/SearchIndexer.php';
require_once __DIR__ . '/classes/BlueprintHelper/BlueprintHelper.php';

/**
 * Class TecArtSearchPlugin
 * @package Grav\Plugin
 */
class TecArtSearchPlugin extends Plugin
{
    protected $indexerFileName  = 'tecart-search-index';

    protected $message          = '';
    protected $message_type     = 'info';

    protected $assetsPath       = 'plugin://tecart-search/assets/';

    //protected $tecartSearchCSS  = 'css/tecart-search.css';
    protected $tecartSearchCSS     = 'css/tecart-search.min.css';
    //protected $tecartSearchJS   = 'js/tecart-search.js';
    protected $tecartSearchJS   = 'js/tecart-search.min.js';

    // if needed an not included in theme
    protected $jqueryLib        = 'vendor/jquery/jquery-3.5.1.min.js';

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                ['onPluginsInitialized', 0]
            ],
            'onTecArtSearchCreateIndex' => ['createSearchIndexJson', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        //proceed if we are in the admin plugin
        if ($this->isAdmin()) {

            $this->enable([
                'onAdminMenu'        => ['onAdminMenu', 0],
                'onBlueprintCreated' => ['onBlueprintCreated', 0]
            ]);

            $uri = $this->grav['uri'];

            if($uri->query() && $uri->query() === 'tecartsearchindexer=create'){
                // call function createSearchIndexJson on this event
                $this->grav->fireEvent('onTecArtSearchCreateIndex');
            }

            return;
        }

        // Enable the main events we are interested in
        $this->enable([
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
        ]);
    }


    /**
     * Set needed variables to display the search results.
     *
     * @return void
     */
  public function onTwigSiteVariables(): void
  {
    $assets = $this->grav['assets'];

    // Add plugin CSS files to the grav assets.
    $assets->addCss($this->assetsPath . $this->tecartSearchCSS, array('rel' => 'preload'));

    // Add plugin JS files to the grav assets.
    $assets->addJs($this->assetsPath . $this->tecartSearchJS, array('loading' => 'async'));

    // Include jQuery via plugin
    if ($this->config->get('plugins.tecart-search.includes_jquery')) {
      $assets->addJs($this->assetsPath . $this->jqueryLib);
    }
  }

    /**
     * Add current directory to twig lookup paths.
     *
     * @return void
     */
    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Register button in Admin Quick Tray
     *
     * @return void
     */
    public function onAdminMenu(): void
    {
        if ($this->config->get('plugins.tecart-search.quick_tray')) {
            $index = [
                'authorize' => $this->config->get('plugins.tecart-search.quick_tray_permissions'),
                'hint' => 'Create Search Index',
                'route' => 'admin/plugins/tecart-search?tecartsearchindexer=create',
                'class' => 'tecart-search-indexer',
                'icon' => 'fa-search'
            ];
            $this->grav['twig']->plugins_quick_tray['tecart-search-indexer'] = $index;
        }
    }

    /**
     * Extend page blueprints with search configuration options.
     *
     * @param Event $event
     */
    public function onBlueprintCreated(Event $event)
    {
        static $inEvent = false;

        /** @var Data\Blueprint $blueprint */
        $blueprint = $event['blueprint'];
        if (!$inEvent && $blueprint->get('form/fields/tabs', null, '/')) {
            if (!in_array($blueprint->getFilename(), array_keys($this->grav['pages']->modularTypes()))) {
                $inEvent = true;
                $blueprints = new Data\Blueprints(__DIR__ . '/blueprints/');
                $extends = $blueprints->get('tecart-search-page');
                $blueprint->extend($extends, true);
                $inEvent = false;
            }
        }
    }

    /**
     * Create search index file
     *
     * @return void
     */
    public function createSearchIndexJson(): void
    {
        $file = SearchIndexer::getSearchIndexFile($this->indexerFileName);

        $createSearchIndexer = SearchIndexer::createIndexData($file);

        if ($createSearchIndexer === false) {
            $this->message = 'Index could not be created.';
            $this->message_type = 'error';
        } else {
            $this->message = 'Index successfully created in folder "'.  $file .'"';
        }
        $this->grav['admin']->setMessage($this->message, $this->message_type);

        $this->grav->redirect($this->grav['uri']->url);
    }
}
