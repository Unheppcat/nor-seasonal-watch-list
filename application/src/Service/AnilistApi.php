<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace App\Service;

use App\Entity\Show;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;

class AnilistApi
{
    private string $anilistApiBase = 'https://graphql.anilist.co';

    /**
     * @param int $anilistId
     * @return array|null
     * @throws GuzzleException|JsonException
     */
    public function fetch(int $anilistId): ?array
    {
        try {
            $http = new Client;
            $response = $http->post($this->anilistApiBase, [
                'json' => [
                    'query' => $this->constructQuery($anilistId)
                ]
            ]);
            $statusCode = $response->getStatusCode();
            if (($statusCode >= 200) && ($statusCode < 300)) {
                try {
                    $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                    return $data['data']['Media'];
                } /** @noinspection PhpUnusedLocalVariableInspection */ catch (Exception $e) {
                    return null;
                }
            }
            return null;
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (RequestException|Exception $e) {
            return null;
        }
    }

    public function updateShow(Show $show, array $data): void
    {
//        echo("<pre>\n"); print_r($data); die();
        $show->setJapaneseTitle($data['title']['romaji']);
        if (empty($data['title']['english'])) {
            if (empty($show->getEnglishTitle())) {
                $show->setEnglishTitle($data['title']['romaji']);
            }
        } else {
            $show->setEnglishTitle($data['title']['english']);
        }
        $show->setFullEnglishTitle($data['title']['english']);
        $show->setFullJapaneseTitle($data['title']['native']);
        $show->setDescription($data['description']);
        $show->setHashtag($data['hashtag']);
        $show->setCoverImageMedium($data['coverImage']['medium']);
        $show->setCoverImageLarge($data['coverImage']['large']);
        $show->setMalId((int)$data['idMal']);
        if (empty($data['synonyms'])) {
            $show->setSynonyms(null);
        } else {
            try {
                $show->setSynonyms(json_encode($data['synonyms'], JSON_THROW_ON_ERROR));
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch (JsonException $e) {
                $show->setSynonyms('');
            }
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
        siteUrl,
        idMal
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
