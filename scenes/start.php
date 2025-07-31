<?php
return [
    'title' => 'The Enchanted Forest',
    'text' => 'You stand at the edge of a mystical forest. Ancient trees tower above you, their branches whispering secrets in the wind. A narrow path splits into three directions ahead.',
    'image_alt' => 'A mystical forest entrance with three diverging paths',
    'choices' => [
        'left' => [
            'text' => 'Take the left path through the dark woods',
            'next_scene' => 'dark_woods',
            'hp_change' => -5
        ],
        'center' => [
            'text' => 'Follow the center path toward the light',
            'next_scene' => 'sunny_meadow',
            'hp_change' => 5
        ],
        'right' => [
            'text' => 'Choose the right path along the river',
            'next_scene' => 'riverside',
            'add_item' => 'River Stone'
        ]
    ]
];
?>