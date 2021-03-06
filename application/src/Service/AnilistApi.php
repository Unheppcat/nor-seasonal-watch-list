<?php

namespace App\Service;

use App\Entity\Show;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AnilistApi
{
    private $anilistApiBase = 'https://graphql.anilist.co';

    /**
     * @param int $anilistId
     * @return array|null
     * @throws GuzzleException
     */
    public function fetch(int $anilistId): ?array
    {
        $http = new Client;
        $response = $http->post($this->anilistApiBase, [
            'json' => [
                'query' => $this->constructQuery($anilistId)
            ]
        ]);
        $statusCode = $response->getStatusCode();
        if (($statusCode >= 200) && ($statusCode < 300)) {
            $data = json_decode($response->getBody()->getContents(), true);
            return $data['data']['Media'];
        }
        return null;
    }

    public function updateShow(Show $show, array $data): void
    {
//        echo("<pre>\n"); print_r($data); die();
        $show->setJapaneseTitle($data['title']['romaji']);
        $show->setEnglishTitle($data['title']['english']);
        $show->setFullEnglishTitle($data['title']['english']);
        $show->setFullJapaneseTitle($data['title']['native']);
        $show->setDescription($data['description']);
        $show->setHashtag($data['hashtag']);
        $show->setCoverImageMedium($data['coverImage']['medium']);
        $show->setCoverImageLarge($data['coverImage']['large']);
        if (empty($data['synonyms'])) {
            $show->setSynonyms(null);
        } else {
            $show->setSynonyms(json_encode($data['synonyms']));
        }
        $show->setSiteUrl($data['siteUrl']);
    }

    private function constructQuery(int $anilistId): string
    {
        return <<<EOF
{
    Media (id: $anilistId, type: ANIME) {
        id,
        title {
            romaji,
            english,
            native
        },
        description,
        hashtag,
        coverImage {
            medium,
            large
        },
        synonyms,
        siteUrl
    }
}
EOF;
    }

    // Query to retrieve a list of show titles for a single season
    /*
{
  Page (page: 1, perPage: 10) {
    pageInfo {
      total,
      currentPage,
      lastPage,
      hasNextPage,
      perPage
    },
    media (season: WINTER, seasonYear: 2021, status: RELEASING, type: ANIME) {
      id,
      status,
      season,
      seasonYear,
      seasonInt,
      title {
        romaji,
        english,
        native
      },
    }
  }
}
     */

}
