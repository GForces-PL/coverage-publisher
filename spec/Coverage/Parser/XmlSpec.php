<?php

use Coverage\Parser;
use Coverage\Parser\Xml;

describe(Xml::class, function () {
    given('parser', function () {
        return new Xml;
    });

    describe('->parse()', function () {
        beforeEach(function () {
            $xml = (object) ['project' => (object) ['metrics' => [Xml::COVERED_LINES => 849, Xml::ALL_LINES => 1000]]];
            allow('simplexml_load_file')->toBeCalled()->andReturn($xml);
        });

        it('parses clover XML file into result object', function () {
            $result = $this->parser->parse('clover.xml');
            expect($result)->toBeAnInstanceOf(Parser\Result::class);
        });

        it('passes metrics to the result object', function () {
            $result = $this->parser->parse('clover.xml');
            expect($result->getPercentage())->toEqual(84.9);
        });
    });
});
