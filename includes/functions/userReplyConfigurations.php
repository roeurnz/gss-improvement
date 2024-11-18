<?php
$arr = [
    'select_lang' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_lang_english'),
            _l('button_lang_khmer')
        ]
    ],
    'register_req_photo' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false,
    ],
    'register_req_id' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => true,
        'button' => [
            _l('skip_and_send')
        ]
    ],
    'clock_out_done' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_clock_in')
        ]
    ],
    'approved' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_clock_in')
        ]
    ],
    'clock_in_done' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_start_break'),
            _l('button_start_visit'),
            _l('button_clock_out'),
            _l('button_yes'),
            _l('button_remind_later')
        ]
    ],
    'clock_out_yes_no' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_yes'), 
            _l('button_no')
        ]
    ],
    'clock_out_live_location' => [
        'live_location' => true,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'clock_out_share_selfie' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false
    ],
    'clock_in_live_location' => [
        'live_location' => true,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'clock_in_req_selfie' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false
    ],
    'start_break_req_location' => [
        'live_location' => true,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'start_break_req_selfie' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false
    ],
    'on_break' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_end_break'),
            _l('button_clock_out'),
            _l('button_yes'),
            _l('button_remind_later')
        ]
    ],
    'end_break_req_location' => [
        'live_location' => true,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'end_break_req_selfie' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false
    ],
    'start_visit_req_location' => [
        'live_location' => true,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'start_visit_req_selfie' => [
        'live_location' => false,
        'location' => false,
        'image' => true,
        'freetext' => false,
        'button' => false
    ],
    'start_visit_req_note' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => true,
        'button' => false
    ],
    'on_visit' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => [
            _l('button_end_visit'),
            _l('button_clock_out'),
            _l('button_yes'),
            _l('button_remind_later')
        ]
    ],
    'end_visit_req_location' => [
        'live_location' => true,
        'location' => false,
        'image' => false,
        'freetext' => false,
        'button' => false
    ],
    'end_visit_req_note' => [
        'live_location' => false,
        'location' => false,
        'image' => false,
        'freetext' => true,
        'button' => false
    ]
];

define('ACCEPTABLE_REPLY', $arr);