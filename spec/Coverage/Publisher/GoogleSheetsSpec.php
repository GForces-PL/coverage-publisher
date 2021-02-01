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
            allow('getenv')->toBeCalled()->with(GoogleSheets::SPREADSHEET_ID_ENV)->andReturn('abc123');
            $response = $this->response;
            allow($this->service->spreadsheets_values)->toReceive('append')->andRun(function (
                $spreadsheetId,
                $range,
                Google_Service_Sheets_ValueRange $postBody,
                $optParams
            ) use ($response) {
                expect($spreadsheetId)->toEqual('abc123');
                expect($range)->toEqual('V10');
                expect($postBody->getValues())->toEqual([['14/12/2020', 87.9]]);
                expect($optParams)->toEqual(['valueInputOption' => 'USER_ENTERED']);
                return $response;
            });
            $this->publisher->publish('V10', 87.9);
        });

        it('accepts coverage as array', function () {
            $response = $this->response;
            allow($this->service->spreadsheets_values)->toReceive('append')->andRun(function () use ($response) {
                $postBody = func_get_arg(2);
                expect($postBody->getValues())->toEqual([['14/12/2020', '', '', 80.65, '15493/19209', 65.98, '7466/11315', 13.86, '8975/64768', 'N/A', 83.85, 90]]);
                return $response;
            });
            $this->publisher->publish('V10', ['', '', 80.65, '15493/19209', 65.98, '7466/11315', 13.86, '8975/64768', 'N/A', 83.85, 90]);
        });

        it('returns message containing details on appended row', function () {
            allow($this->service->spreadsheets_values)->toReceive('append')->andReturn($this->response);
            $this->updates->updatedCells = 2;
            $this->updates->updatedRange = "'V10'!A3:B3";
            expect($this->publisher->publish('V10', 87.9))->toEqual(
                "Appended 2 cells at 'V10'!A3:B3 with: 14/12/2020, 87.9"
            );
        });

        context('when exception is thrown by service', function () {
            it('returns failure message containing details on the exception', function () {
                allow($this->service->spreadsheets_values)->toReceive('append')->andRun(function () {
                    throw new \Google\Exception('something went wrong');
                });
                expect($this->publisher->publish('V10', 87.9))->toEqual(
                    'Failed to publish coverage: something went wrong'
                );
            });
        });
    });
});
