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
//        $client->setApplicationName($appName);
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setAuthConfig(json_decode(getenv('GOOGLE_API_CREDENTIALS'), true));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $token = getenv('GOOGLE_API_TOKEN');
        if ($token) {
            $accessToken = json_decode($token, true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Google\Exception(join(', ', $accessToken));
                }
            }

            $newToken = json_encode($client->getAccessToken());
            putenv("GOOGLE_API_TOKEN='$newToken'");
        }

        return $client;
    }

    private function getResultMessage(Google_Service_Sheets_AppendValuesResponse $response, array $row)
    {
        $updates = $response->getUpdates();
        $rowAsString = implode(', ', $row);
        return "Appended $updates->updatedCells cells at $updates->updatedRange with: $rowAsString";
    }
}