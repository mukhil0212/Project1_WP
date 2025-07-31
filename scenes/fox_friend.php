<?php
return [
    'title' => 'The Fox\'s Gratitude',
    'text' => 'You carefully tend to the fox\'s wounds. As you help, the fox\'s eyes begin to glow with magical light. "Thank you, kind soul," it speaks telepathically. "I am a guardian of this forest. Your compassion has earned you a powerful ally."',
    'image_alt' => 'A magical fox with glowing eyes showing gratitude to the player',
    'choices' => [
        'continue' => [
            'text' => 'Continue deeper into the forest with your new ally',
            'next_scene' => 'victory',
            'hp_change' => 25
        ],
        'rest' => [
            'text' => 'Rest and recover with the fox',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 30
        ]
    ]
];
?>