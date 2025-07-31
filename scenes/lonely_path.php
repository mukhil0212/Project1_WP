<?php
return [
    'title' => 'The Lonely Path',
    'text' => 'You continue alone through the dark woods. The path is treacherous, but you press on with determination. Eventually, you see light ahead.',
    'image_alt' => 'A solitary figure walking through a dark forest toward distant light',
    'choices' => [
        'light' => [
            'text' => 'Head toward the light',
            'next_scene' => 'victory',
            'hp_change' => 0
        ],
        'careful' => [
            'text' => 'Proceed carefully and rest first',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 5
        ]
    ]
];
?>
