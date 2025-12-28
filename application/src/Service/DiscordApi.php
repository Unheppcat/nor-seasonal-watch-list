<?php /** @noinspection PhpUnused */
/** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedClassInspection */

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class DiscordApi
{
    private string $rootUrl;
    private string $token;
    private string $botToken;

    /**
     * DiscordApi constructor.
     * @param string $rootUrl
     * @param string $botToken
     */
    public function __construct(
        string $rootUrl,
        string $botToken
    ) {
        $this->rootUrl = $rootUrl;
        $this->botToken = $botToken;
    }

    public function initialize(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getMe(): ?array
    {
        $result = $this->sendRequest('GET', '/users/@me');
        if ($result && $result->getStatusCode() > 100 && $result->getStatusCode() < 300) {
            return json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
        }
        return null;
    }

//    /**
//     * @return array|null
//     * @throws GuzzleException
//     * @throws JsonException
//     */
//    public function getMyGuilds(): ?array
//    {
//        $result = $this->sendRequest('GET', '/users/@me/guilds');
//        if ($result && (int)$result->getStatusCode() > 100 && (int)$result->getStatusCode() < 300) {
//            return json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
//        }
//        return null;
//    }

    /**
     * @param string $guildId
     * @param string $memberId
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getGuildMemberInfo(string $guildId, string $memberId): ?array
    {
        static $info = [];
        if (isset($info["$guildId:$memberId"])) {
            return $info["$guildId:$memberId"];
        }
        $result = $this->sendRequest('GET', '/guilds/' . $guildId . '/members/' . $memberId, null, true);
        if ($result && $result->getStatusCode() > 100 && $result->getStatusCode() < 300) {
            $data = json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $info["$guildId:$memberId"] = $data;
            return $data;
        }
        return null;
    }

    /**
     * @param string $guildId
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getGuild(string $guildId): ?array
    {
        static $guilds = [];
        if (isset($guilds[$guildId])) {
            return $guilds[$guildId];
        }
        $result = $this->sendRequest('GET', '/guilds/' . $guildId, null, true);
        if ($result && $result->getStatusCode() > 100 && $result->getStatusCode() < 300) {
            $data = json_decode($result->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $guilds[$guildId] = $data;
            return $data;
        }
        return null;
    }

    /**
     * @param string $guildId
     * @return array|null
     * @throws GuzzleException|JsonException
     */
    public function getGuildRoles(string $guildId): ?array
    {
        $guild = $this->getGuild($guildId);
        if ($guild === null) {
            return null;
        }
        return $guild['roles'];
    }

    /**
     * @param string $guildId
     * @param string $memberId
     * @return array|null
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getGuildRolesForMember(string $guildId, string $memberId): ?array
    {
        $guildRoles = $this->getGuildRoles($guildId);
        $memberInfo = $this->getGuildMemberInfo($guildId, $memberId);
        if ($guildRoles === null || $memberInfo === null) {
            return null;
        }
        $memberRoleIds = $memberInfo['roles'];
        $data = [];
        foreach ($memberRoleIds as $memberRoleId) {
            $data[$memberRoleId] = $this->findRoleInfoByRoleId($memberRoleId, $guildRoles);
        }
        return $data;
    }

    /**
     * @param string $norGuildId
     * @param string $userDiscordId
     * @return string|null
     * @throws GuzzleException
     * @throws JsonException
     */
    public function getNicknameForMember(string $norGuildId, string $userDiscordId): ?string
    {
        $memberInfo = $this->getGuildMemberInfo($norGuildId, $userDiscordId);
        return $memberInfo['nick'];
    }

    /**
     * @param string $roleId
     * @param array $allRolesInfo
     * @return array|null
     */
    private function findRoleInfoByRoleId(string $roleId, array $allRolesInfo): ?array
    {
        foreach ($allRolesInfo as $roleInfo) {
            if ($roleInfo['id'] === $roleId) {
                return $roleInfo;
            }
        }
        return null;
    }

    /**
     * @param $method
     * @param $endpoint
     * @param null $body
     * @param bool $asBot
     * @return ResponseInterface|null
     * @throws GuzzleException
     */
    public function sendRequest($method, $endpoint, $body = null, bool $asBot = false): ?ResponseInterface
    {
        if ($asBot) {
            $authorization = 'Bot ' . $this->botToken;
        } else {
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($this->token === null) {
                throw new RuntimeException('Token not set, library has not been initialized');
            }
            $authorization = 'Bearer ' . $this->token;
        }
        return (new Client(['exceptions' => false]))->request($method, $this->rootUrl . $endpoint, [
            'body' => $body,
            'headers' => [ 'Authorization' => $authorization],
            'http_errors' => false
        ]);
    }
}
