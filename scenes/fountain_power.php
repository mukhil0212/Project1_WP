<?php
return [
    'title' => 'The Fountain of Power',
    'text' => 'The crystal-clear water fills you with magical energy. You feel stronger and more confident. A hidden path opens behind the fountain, leading to what appears to be the final challenge.',
    'image_alt' => 'A magical fountain with glowing water and a hidden path',
    'choices' => [
        'challenge' => [
            'text' => 'Face the final challenge',
            'next_scene' => 'victory',
            'hp_change' => 0
        ],
        'rest' => [
            'text' => 'Rest and prepare first',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 10
        ]
    ]
];
?>
