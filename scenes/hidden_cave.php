<?php
return [
    'title' => 'The Hidden Cave',
    'text' => 'You discover a secret cave behind some bushes. Inside, ancient crystals glow with mysterious light. You sense this place holds great power, but also danger.',
    'image_alt' => 'A mysterious cave with glowing crystals',
    'choices' => [
        'crystals' => [
            'text' => 'Touch the glowing crystals',
            'next_scene' => 'victory',
            'hp_change' => 15,
            'add_item' => 'Crystal Power'
        ],
        'explore' => [
            'text' => 'Explore deeper into the cave',
            'next_scene' => 'defeat',
            'hp_change' => -20
        ],
        'leave' => [
            'text' => 'Leave the cave and find another path',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 0
        ]
    ]
];
?>
