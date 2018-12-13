<?php

namespace App\User;

use SilverStripe\ORM\DataExtension;

class RandomMemberExtension extends DataExtension {

    private static $db = [
        'Cell' => 'Varchar(25)',
        'ProfilePic' => 'Varchar(512)',
    ];

}
