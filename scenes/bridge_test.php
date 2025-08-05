<?php
return [
    'title' => 'The Bridge Test',
    'text' => 'As you step onto the rickety bridge, it creaks ominously under your weight. Halfway across, you notice the planks are enchanted - they glow with ancient runes. A voice echoes: "Only the brave may pass."',
    'image_alt' => 'A magical bridge with glowing runes over a mystical river',
    'choices' => [
        'brave' => [
            'text' => 'Declare your bravery and continue',
            'next_scene' => 'victory',
            'hp_change' => 10,
            'add_item' => 'Bridge Guardian\'s Blessing'
        ],
        'careful' => [
            'text' => 'Proceed carefully and humbly',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 5
        ],
        'retreat' => [
            'text' => 'Retreat back to safety',
            'next_scene' => 'riverside',
            'hp_change' => -5
        ]
    ]
];
?>
