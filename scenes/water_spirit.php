<?php
return [
    'title' => 'The Water Spirit',
    'text' => 'As you swim across the river, a beautiful water spirit emerges from the depths. Her voice is like flowing water: "Brave swimmer, you have shown respect for my domain. I offer you a choice of blessings."',
    'image_alt' => 'A graceful water spirit emerging from crystal-clear river water',
    'choices' => [
        'healing' => [
            'text' => 'Ask for the blessing of healing',
            'next_scene' => 'victory',
            'hp_change' => 30,
            'add_item' => 'Spirit\'s Healing Touch'
        ],
        'wisdom' => [
            'text' => 'Ask for the blessing of wisdom',
            'next_scene' => 'victory',
            'hp_change' => 10,
            'add_item' => 'Spirit\'s Ancient Knowledge'
        ],
        'humble' => [
            'text' => 'Humbly decline and thank the spirit',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 15,
            'add_item' => 'Spirit\'s Respect'
        ]
    ]
];
?>
