<?php
return [
    'title' => 'Victory in Battle',
    'text' => 'After a fierce battle, you emerge victorious! Though wounded, you feel stronger and more experienced. The path ahead is now clear.',
    'image_alt' => 'A victorious warrior standing over defeated enemies',
    'choices' => [
        'continue' => [
            'text' => 'Continue on your journey',
            'next_scene' => 'victory',
            'hp_change' => 5
        ],
        'rest' => [
            'text' => 'Rest and tend to your wounds',
            'next_scene' => 'peaceful_rest',
            'hp_change' => 10
        ]
    ]
];
?>
