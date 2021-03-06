<?php
namespace App\Services;

use GuzzleHttp\Client;
use PHPHtmlParser\Dom;

/**
 * 筑波大学最強システム Twins
 */
class Twins
{

    private $client, $exec_key;
    const TWINS_URL = 'https://twins.tsukuba.ac.jp/campusweb/';

    function auth($id, $pw)
    {
        $this->client = new client([
            'base_uri' => self::TWINS_URL,
            'cookies' => true
        ]);
        $this->client->get('campussquare.do');

        $form_params = [
            "userName"=> $id,
            "password"=> $pw,
            "_flowId"=> "USW0009000-flow",
            "locale"=> "ja_JP"
        ];

        $res = $this->client->post('campussquare.do', ['form_params' => $form_params, 'allow_redirects' => false]);
        $url = $res->getHeader('location')[0];
        $res = $this->client->get($url, ['allow_redirects' => false]);
        $url = $res->getHeader('location')[0];
        $this->exec_key = explode('=', parse_url($url)['query'])[1];
        $res = $this->client->get($url, ['allow_redirects' => false]);
    }

    function getNotices()
    {
        // exec_keyを取得する
        $form_params = [
            '_flowId' => 'KJW0001100-flow'
        ];

        $res = $this->client->post('campussquare.do', ['form_params' => $form_params, 'allow_redirects' => false]);
        $url = $res->getHeader('location')[0];
        $this->exec_key = explode('=', parse_url($url)['query'])[1];

        // 掲示を取得する
        $query = [
            "_flowExecutionKey"	=> $this->exec_key,
            "_eventId"	=> "dispKeijiListGenre",
            "keijitype"	=> "4",
            "genrecd"	=> "262"
        ];

        $res = $this->client->get('campussquare.do', ['query' => $query, 'allow_redirects' => false]);
        $url = $res->getHeader('location')[0];
        $res = $this->client->get($url, ['allow_redirects' => false]);

        // HTMLをパース
        $noticeTitles = [];
        $dom = new Dom();
        $dom->loadStr($res->getBody()->getContents(), []);
        $links=$dom->find('tr > td > a');
        foreach ($links as $link) {
            if($link->innerHtml == '授業掲示板') break;
            $noticeTitles[] = ['title' => $link->innerHtml, 'url' => self::TWINS_URL.$link->tag->getAttribute('href')['value']];
        }

        return $noticeTitles;
    }
}
