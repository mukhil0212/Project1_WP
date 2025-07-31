<?php
return [
    'title' => 'The Dark Woods',
    'text' => 'The path grows darker as twisted branches block out the sun. You hear rustling in the undergrowth. Suddenly, a wounded fox limps onto the path, looking at you with pleading eyes.',
    'image_alt' => 'A dark forest path with a wounded fox',
    'choices' => [
        'help' => [
            'text' => 'Help the injured fox',
            'next_scene' => 'fox_friend',
            'hp_change' => -10,
            'add_item' => 'Fox\'s Blessing'
        ],
        'ignore' => [
            'text' => 'Ignore the fox and continue',
            'next_scene' => 'lonely_path',
            'hp_change' => 0
        ],
        'flee' => [
            'text' => 'Run back to the forest entrance',
            'next_scene' => 'start',
            'hp_change' => -5
        ],
        'fight' => [
            'text' => 'Fight the dark creatures lurking nearby',
            'next_scene' => 'battle_victory',
            'hp_change' => -15
        ]
    ]
];
?>