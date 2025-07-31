<?php
return [
    'title' => 'The Sunny Meadow',
    'text' => 'You emerge into a beautiful meadow filled with wildflowers. The warm sun energizes you, and you notice a sparkling fountain in the center. An old sage sits beside it, reading an ancient tome.',
    'image_alt' => 'A bright meadow with wildflowers, a fountain, and an old sage',
    'choices' => [
        'fountain' => [
            'text' => 'Drink from the magical fountain',
            'next_scene' => 'fountain_power',
            'hp_change' => 20,
            'add_item' => 'Crystal Water'
        ],
        'sage' => [
            'text' => 'Approach the wise sage',
            'next_scene' => 'sage_wisdom',
            'hp_change' => 0,
            'add_item' => 'Ancient Knowledge'
        ],
        'explore' => [
            'text' => 'Explore the meadow for secrets',
            'next_scene' => 'hidden_cave',
            'hp_change' => 5
        ]
    ]
];
?>