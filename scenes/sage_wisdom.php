<?php
return [
    'title' => 'The Wise Sage',
    'text' => 'The ancient sage looks up from his tome and smiles knowingly. "Young adventurer," he says, "you seek the path to victory. I can guide you, but the choice must be yours."',
    'image_alt' => 'An old sage with a long beard holding an ancient book',
    'choices' => [
        'wisdom' => [
            'text' => 'Accept the sage\'s wisdom',
            'next_scene' => 'victory',
            'hp_change' => 0,
            'add_item' => 'Sage\'s Blessing'
        ],
        'decline' => [
            'text' => 'Politely decline and continue alone',
            'next_scene' => 'hidden_cave',
            'hp_change' => 0
        ]
    ]
];
?>
