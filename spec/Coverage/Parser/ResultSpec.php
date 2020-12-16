<?php

use Coverage\Parser\Result;

describe(Result::class, function () {
    given('result', function () {
        return new Result(849, 1000);
    });

    describe('->getPercentage()', function () {
        context('when the number of all lines is not 0', function () {
            given('result', function () {
                return new Result(849, 1000);
            });

            it('returns the percentage of covered lines', function () {
                expect($this->result->getPercentage())->toEqual(84.9);
            });

            it('allows to specify precision', function () {
                expect($this->result->getPercentage(0))->toEqual(85.0);
            });
        });

        context('when the number of all lines is 0', function () {
            given('result', function () {
                return new Result(0, 0);
            });

            it('returns 0.0', function () {
                expect($this->result->getPercentage())->toEqual(0.0);
            });
        });
    });

    describe('->getLinesRatio()', function () {
        it('returns covered lines ratio as string', function () {
            expect($this->result->getLinesRatio())->toEqual('849/1000');
        });
    });
});
