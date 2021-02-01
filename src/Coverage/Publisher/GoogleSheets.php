<?php

namespace Coverage\Publisher;

use Coverage\Publisher;
use Google;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_AppendValuesResponse;
use Google_Service_Sheets_ValueRange;

class GoogleSheets implements Publisher
{
    const SPREADSHEET_ID_ENV = 'GOOGLE_SPREADSHEET_ID';
    const API_TOKEN_ENV = 'GOOGLE_API_TOKEN';
    const API_CREDENTIALS_ENV = 'GOOGLE_API_CREDENTIALS';

    private $service;

    /**
     * @inheritdoc
     */
    public function publish($a1NotationRange, $coverage)
    {
        try {
            return $this->send($a1NotationRange, $coverage);
        } catch (\Exception $e) {
            return "Failed to publish coverage: {$e->getMessage()}";
        }
    }

    /**
     * @param string $a1NotationRange
     * @param float|array $coverage
     * @return string
     * @throws Google\Exception
     */
    private function send($a1NotationRange, $coverage)
    {
        $coverageArray = is_array($coverage) ? $coverage : [$coverage];
        $row = array_merge([strftime('%d/%m/%Y')], $coverageArray);
        $response = $this->append($row, $a1NotationRange);
        return $this->getResultMessage($response, $row);
    }

    /**
     * @return Google_Service_Sheets
     * @throws Google\Exception
     */
    private function getService()
    {
        if (!$this->service) {
            $client = $this->getClient();
            $this->service = new Google_Service_Sheets($client);
        }
        return $this->service;
    }

    /**
     * @return Google_Client
     * @throws Google\Exception
     */
    private function getClient()
    {
        $client = new Google_Client();
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig($this->getAuthConfig());
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $this->setAccessTokenFromEnv($client);
        $client->isAccessTokenExpired() && $this->refreshAccessToken($client);
        return $client;
    }

    private function getAuthConfig()
    {
        $credentials = getenv(self::API_CREDENTIALS_ENV);
        return $this->getJson($credentials);
    }

    private function getJson($string)
    {
        return preg_match('/^1\./', Google_Client::LIBVER) ? $string : json_decode($string, true);
    }

    private function setAccessTokenFromEnv(Google_Client $client)
    {
        $token = getenv(self::API_TOKEN_ENV);
        if ($token) {
            $accessToken = $this->getJson($token);
            $client->setAccessToken($accessToken);
        }
    }

    private function refreshAccessToken(Google_Client $client)
    {
        $client->refreshToken($client->getRefreshToken());
        $newToken = json_encode($client->getAccessToken());
        putenv(self::API_TOKEN_ENV . "='$newToken'");
    }

    private function append(array $row, $a1NotationRange)
    {
        $spreadsheet = $this->getService()->spreadsheets_values;
        $params = [
            'spreadsheetId' => getenv(self::SPREADSHEET_ID_ENV),
            'range' => $a1NotationRange,
            'postBody' => new Google_Service_Sheets_ValueRange(['values' => [$row]]),
            'valueInputOption' => 'USER_ENTERED',
        ];
        return $spreadsheet->call('append', [$params], Google_Service_Sheets_AppendValuesResponse::class);
    }

    private function getResultMessage(Google_Service_Sheets_AppendValuesResponse $response, array $row)
    {
        $updates = $response->getUpdates();
        $rowAsString = implode(', ', $row);
        return "Appended $updates->updatedCells cells at $updates->updatedRange with: $rowAsString";
    }
}
