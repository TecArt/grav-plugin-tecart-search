<?php

namespace Grav\Plugin\TecartSearch\Classes\SearchIndexer;

use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Page\Collection;


/**
 * Tecart Cookie Manager Plugin Cookie Manager Class
 *
 */
class SearchIndexer {

    /**
     *
     * get index file
     * @return string
     */
    public static function getSearchIndexFile($indexerFileName) {

        $grav = Grav::instance();

        //location of yaml files
        $dataStorage = 'user://data';

        $config = $grav['config']->get('plugins.tecart-search');
        if(isset($config['index_storage']) && $config['index_storage']  == "pages"){
            $dataStorage = 'page://assets';
            if(!is_dir($dataStorage)) {
                mkdir($dataStorage, 0755, true);
            }
        }

        $file = $grav['locator']->findResource($dataStorage) . DS . $indexerFileName . ".json";

        return $file;
    }

    /**
     * Get all Grav pages that are publish
     *
     * @param $pages
     * @return void
     */
    public static function getPages()
    {
        $grav = Grav::instance();

        //very important the migration  1.6 -> 1.7 which has a section on breaking changes for Admin plugins:
        $grav['admin']->enablePages();

        $pages = Grav::instance()['pages'];

        return $pages;

    }

    /**
     * get data object
     *
     * @param $file
     * @return bool
     */
    public static function createIndexData($file): bool
    {
        $pages = self::getPages();

        if($pages !== null){

            $routes = array_unique($pages->routes());
            ksort($routes);

            $jsonArray = array();

            foreach ($routes as $route => $path) {

                /** @var PageInterface $page */
                $page = $pages->get($path);
                $header = $page->header();
                $page_ignored = $header->tecartsearch['ignore'] ?? false;
                $page_has_higher_priority = $header->tecartsearch['has_higher_priority'] ?? false;

                // only published and routable pages
                if ($page->routable() && $page->published() && $page_ignored === false){

                    $location = $page->canonical();
                    $page_route = $page->url();
                    $page_metadata = '';
                    $page_content =$page->rawMarkdown();

                    if(!empty($header->meta_name_items)) {
                        $page_metadata = array();
                        if(!empty($header->meta_name_items['name'])) {
                            $page_metadata['name'] = $header->meta_name_items['name'];
                        }
                        if(!empty($header->meta_name_items['description'])) {
                            $page_metadata['description'] = $header->meta_name_items['description'];
                        }
                    }

                    if (empty($page_content) && isset($header->content) && !isset($header->content['items']) ) {
                        $page_content = $header->content;
                    }
                    elseif (empty($page_content) && isset($header->hero_content)) {
                        $page_content = $header->hero_content;
                    }

                    $page_data = [
                        'title' => $page->title(),
                        'metadata' => $page_metadata,
                        'content' => $page_content,
                        'route' => $page_route,
                        'location' => $location,
                        'has_higher_priority' => $page_has_higher_priority,
                    ];


                    // Encode post data to JSON data format. Pretty-Print for easy editing
                    array_push($jsonArray, $page_data);
                }
            }

            $sort_key = array_column($jsonArray, 'has_higher_priority');
            array_multisort($sort_key, SORT_DESC, $jsonArray);

            $jsonData = json_encode($jsonArray, JSON_PRETTY_PRINT);
            file_put_contents($file,$jsonData) ;

            return  true;
        }
        return false;
    }
}
