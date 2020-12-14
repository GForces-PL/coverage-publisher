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
    const DEFAULT_SPREADSHEET_ID = '1szsifLaLNsGxLDeDcZD0z0dNM0vQLwQ2UJU6DASa33s';
    const OPTION_SPREADSHEET_ID = 'spreadsheetId';
    const GOOGLE_API_TOKEN_ENV = 'GOOGLE_API_TOKEN';

    private $service;

    /**
     * @inheritdoc
     * @throws Google\Exception
     */
    public function publish($appName, $coverage, array $options = [self::OPTION_SPREADSHEET_ID => self::DEFAULT_SPREADSHEET_ID])
    {
        $spreadsheet = $this->getService()->spreadsheets_values;
        $row = [strftime('%d/%m/%Y'), $coverage];
        $response = $spreadsheet->append(
            $options[self::OPTION_SPREADSHEET_ID],
            $appName,
            new Google_Service_Sheets_ValueRange(['values' => [$row]]),
            ['valueInputOption' => 'USER_ENTERED']
        );
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
        $client->setAuthConfig(json_decode(getenv('GOOGLE_API_CREDENTIALS'), true));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $this->setAccessTokenFromEnv($client);
        $client->isAccessTokenExpired() && $this->refreshAccessToken($client);
        return $client;
    }

    private function setAccessTokenFromEnv(Google_Client $client)
    {
        $token = getenv(self::GOOGLE_API_TOKEN_ENV);
        if ($token) {
            $accessToken = json_decode($token, true);
            $client->setAccessToken($accessToken);
        }
    }

    private function refreshAccessToken(Google_Client $client)
    {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $newToken = json_encode($client->getAccessToken());
        putenv(self::GOOGLE_API_TOKEN_ENV . "='$newToken'");
    }

    private function getResultMessage(Google_Service_Sheets_AppendValuesResponse $response, array $row)
    {
        $updates = $response->getUpdates();
        $rowAsString = implode(', ', $row);
        return "Appended $updates->updatedCells cells at $updates->updatedRange with: $rowAsString";
    }
}
