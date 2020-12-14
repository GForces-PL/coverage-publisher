<?php

use Coverage\Publisher\GoogleSheets;
use Kahlan\Plugin\Double;

describe(GoogleSheets::class, function () {
    given('publisher', function () {
        return new GoogleSheets;
    });

    describe('->publish()', function () {
        given('service', function () {
            $service = Double::instance(['extends' => Google_Service_Sheets::class, 'magicMethods' => true]);
            $service->spreadsheets_values = Double::instance();
            return $service;
        });
        given('updates', function () {
            return Double::instance();
        });
        given('response', function () {
            return Double::instance([
                'extends' => Google_Service_Sheets_AppendValuesResponse::class,
                'stubMethods' => ['getUpdates' => $this->updates]
            ]);
        });

        beforeEach(function () {
            allow($this->publisher)->toReceive('getService')->andReturn($this->service);
            allow('strftime')->toBeCalled()->with('%d/%m/%Y')->andReturn('14/12/2020');
        });

        it('publishes coverage via Google service', function () {
            allow($this->service->spreadsheets_values)->toReceive('append')->andReturn($this->response);
            expect($this->service->spreadsheets_values)->toReceive('append');
            $this->publisher->publish('', 0);
        });

        it('passes appropriate values to the service', function () {
            $response = $this->response;
            allow($this->service->spreadsheets_values)->toReceive('append')->andRun(function (
                $spreadsheetId,
                $range,
                Google_Service_Sheets_ValueRange $postBody,
                $optParams
            ) use ($response) {
                expect($spreadsheetId)->toEqual(GoogleSheets::DEFAULT_SPREADSHEET_ID);
                expect($range)->toEqual('V10');
                expect($postBody->getValues())->toEqual([['14/12/2020', 87.9]]);
                expect($optParams)->toEqual(['valueInputOption' => 'USER_ENTERED']);
                return $response;
            });
            $this->publisher->publish('V10', 87.9);
        });

        it('returns message containing details on appended row', function () {
            allow($this->service->spreadsheets_values)->toReceive('append')->andReturn($this->response);
            $this->updates->updatedCells = 2;
            $this->updates->updatedRange = "'V10'!A3:B3";
            expect($this->publisher->publish('V10', 87.9))->toEqual(
                "Appended 2 cells at 'V10'!A3:B3 with: 14/12/2020, 87.9"
            );
        });
    });
});
