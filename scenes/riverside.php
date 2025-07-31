<?php
return [
    'title' => 'The Mystical Riverside',
    'text' => 'The path follows a crystal-clear river. You can see colorful fish swimming below. A rickety bridge spans the water ahead, while downstream you spot what looks like a treasure chest partially buried in sand.',
    'image_alt' => 'A clear river with a bridge and a partially buried treasure chest',
    'choices' => [
        'bridge' => [
            'text' => 'Cross the rickety bridge carefully',
            'next_scene' => 'bridge_test',
            'hp_change' => -10
        ],
        'treasure' => [
            'text' => 'Investigate the treasure chest',
            'next_scene' => 'treasure_trap',
            'hp_change' => 0
        ],
        'swim' => [
            'text' => 'Swim across the river',
            'next_scene' => 'water_spirit',
            'hp_change' => 10,
            'add_item' => 'Water\'s Blessing'
        ]
    ]
];
?>