<?php
return [
    'title' => 'The Treasure Trap',
    'text' => 'You approach the partially buried chest. As you touch it, magical symbols appear on its surface. It\'s a test! The chest speaks: "Answer correctly and claim your reward, fail and face the consequences."',
    'image_alt' => 'A magical treasure chest with glowing symbols by the riverside',
    'choices' => [
        'wisdom' => [
            'text' => 'Use your wisdom to solve the riddle',
            'next_scene' => 'victory',
            'hp_change' => 0,
            'add_item' => 'Treasure of Wisdom'
        ],
        'force' => [
            'text' => 'Try to force the chest open',
            'next_scene' => 'defeat',
            'hp_change' => -25
        ],
        'leave' => [
            'text' => 'Leave the chest and continue',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 0
        ]
    ]
];
?>
