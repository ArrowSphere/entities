<?php

declare(strict_types=1);

namespace ArrowSphere\Entities\Tests;

use ArrowSphere\Entities\AbstractEntity;
use ArrowSphere\Entities\Property;
use DateTime;

/**
 * Class EntityTest
 */
class EntityTest extends AbstractEntityTest
{
    protected const CLASS_NAME = Team::class;

    public function providerSerialization(): array
    {
        $json = <<<JSON
{
    "id": 12,
    "active": true,
    "name": "Justice League",
    "address": {
        "addressLine1": "1007 Mountain Drive",
        "addressLine2": "Wayne Manor",
        "zip": "12345",
        "city": "Gotham City",
        "state": "NJ",
        "country": "USA"
    },
    "createdAt": "1960-03-01T20:12:23-04:00",
    "members": [
        {
            "name": "Batman",
            "realName": "Bruce Wayne",
            "powers": []
        },
        {
            "name": "Superman",
            "realName": "Clark Kent",
            "powers": [
                "invulnerability",
                "flight",
                "laser eyes"
            ]
        },
        {
            "name": "The Flash",
            "realName": null,
            "powers": [
                "super speed"
            ]
        }
    ]
}
JSON;

        return [
            'standard' => [
                'fields'   => [
                    'id'        => 12,
                    'active'    => true,
                    'createdAt' => '1960-03-01T20:12:23-04:00',
                    'name'      => 'Justice League',
                    'address'   => [
                        'addressLine1' => '1007 Mountain Drive',
                        'addressLine2' => 'Wayne Manor',
                        'zip'          => '12345',
                        'city'         => 'Gotham City',
                        'state'        => 'NJ',
                        'country'      => 'USA',
                    ],
                    'members'   => [
                        [
                            'name'     => 'Batman',
                            'realName' => 'Bruce Wayne',
                            'powers'   => [],
                        ],
                        [
                            'name'     => 'Superman',
                            'realName' => 'Clark Kent',
                            'powers'   => [
                                'invulnerability',
                                'flight',
                                'laser eyes',
                            ],
                        ],
                        [
                            'name'     => 'The Flash',
                            'realName' => null,
                            'powers'   => [
                                'super speed',
                            ],
                        ],
                    ],
                ],
                'expected' => $json,
            ],
        ];
    }
}

/**
 * Class Team
 */
class Team extends AbstractEntity
{
    /**
     * @Property(name="id", type="int", required=true)
     *
     * @var int
     */
    private $id;

    /**
     * @Property(type="bool", required=true)
     *
     * @var bool
     */
    private $active;

    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $name;

    /**
     * @Property(type="ArrowSphere\Entities\Tests\Address", required=true)
     *
     * @var Address
     */
    private $address;

    /**
     * @Property(type="\DateTime", required=true)
     *
     * @var DateTime
     */
    private $createdAt;

    /**
     * @Property(type="ArrowSphere\Entities\Tests\Member", isArray=true, required=true)
     *
     * @var Member[]
     */
    private $members;
}

/**
 * Class Address
 */
class Address extends AbstractEntity
{
    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $addressLine1;

    /**
     * @Property(nullable=true)
     *
     * @var string|null
     */
    private $addressLine2;

    /**
     * @Property(nullable=true)
     *
     * @var string|null
     */
    private $addressLine3;

    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $zip;

    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $city;

    /**
     * @Property(nullable=true)
     *
     * @var string|null
     */
    private $state;

    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $country;
}

/**
 * Class Member
 */
class Member extends AbstractEntity
{
    /**
     * @Property(required=true)
     *
     * @var string
     */
    private $name;

    /**
     * @Property(required=true, nullable=true)
     *
     * @var string
     */
    private $realName;

    /**
     * @Property(isArray=true, required=true)
     *
     * @var string[]
     */
    private $powers;
}
