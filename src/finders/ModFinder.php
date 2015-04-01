<?php

/**
 * This file is part of modHelper
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class ModFinder
{

    /**
     * @type string
     */
    private $workshopUrl;

    /**
     * @type string
     */
    private $modUrl = "http://steamcommunity.com/sharedfiles/filedetails/?id=";

    /**
     * @type YamlCache
     */
    private $cache;

    /**
     * @param YamlCache $cache
     */
    public function __construct(YamlCache $cache)
    {
        $this->workshopUrl = "http://steamcommunity.com/id/%s/myworkshopfiles/".
            "?appid=244850&sort=score&browsefilter=myfavorites&view=imagewall&p=%d&numperpage=9";
        $this->cache = $cache;
    }

    /**
     * @param string $userId
     *
     * @return array
     */
    public function findMods($userId)
    {
        $page    = 1;
        $crawler = new Crawler(file_get_contents(sprintf($this->workshopUrl, $userId, $page)));
        $div     = '.workshopItemPreviewHolder';
        $mods    = [];

        $tmp = [];
        while ($crawler->filter($div)->count() > 0) {
            $tmp = array_merge(
                $tmp,
                $crawler->filter($div)->each(
                    function(Crawler $node, $i) use($page) { return $this->buildMod($node, $i); }
                )
            );

            $url = sprintf($this->workshopUrl, $userId, ++$page);
            $crawler = new Crawler(file_get_contents($url));
        }

        foreach ($tmp as $mod) {
            $mods[$mod['id']] = $mod;
        }

        return $mods;
    }

    private function buildMod(Crawler $node, $index)
    {
        $id = (int) str_replace('sharedfile_', '', $node->attr('id'));
        if ($this->cache->has($id, 60*60*8)) {
            return $this->cache->get($id);
        }

        $crawler = new Crawler(file_get_contents($this->modUrl.$id));

        $posted  = $this->getDate($crawler, 1);
        $updated = $this->getDate($crawler, 2);

        $mod = [
            'id'      => $id,
            'image'   => $this->getImage($crawler),
            'title'   => $crawler->filter('.workshopItemTitle')->text(),
            'posted'  => $posted,
            'updated' => $updated,
            'rating'  => $this->getRating($crawler),
            'enabled' => true
        ];

        return $this->cache->set($id, $mod);
    }

    private function getImage(Crawler $crawler)
    {
        try {
            return str_replace('268x268.resizedimage', '', $crawler->filter('#previewImageMain')->attr('src'));
        } catch (\Exception $e) {
            return str_replace('637x358.resizedimage', '', $crawler->filter('#previewImage')->attr('src'));
        }
    }

    private function getRating(Crawler $crawler)
    {
        $img = basename($crawler->filter('.fileRatingDetails img')->attr('src'));
        if (is_nan($img[0])) {
            ldd($img);
        }

        return [
            'stars' => (int) $img[0],
            'ratings' => (int) str_replace(' ratings', '', $crawler->filter('.numRatings')->text())
        ];
    }

    private function getDate(Crawler $crawler, $eq)
    {
        try {
            $date = new \DateTime(str_replace('@', ' ', $crawler->filter('.detailsStatsRight')->eq($eq)->text()));

            return $date->format('Y-m-d h:i:sa');
        } catch (\Exception $e) {
            return 'No Date';
        }
    }
}
