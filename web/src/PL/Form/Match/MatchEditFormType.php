<?php

namespace App\PL\Form\Match;

use App\BL\Match\MatchModel;
use App\BL\Util\DateTimeUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start_time', DateTimeType::class, [
                'label' => 'Start time - Warning collisions with other matches are not checked',
                'getter' => fn (MatchModel $match, FormInterface $form): \DateTimeInterface => $match->getStartTime(),
                'setter' => function (MatchModel $match, \DateTimeInterface $start_time, FormInterface $form): void {
                    $match->setStartTime($start_time);
                }
            ])
            ->add('duration', TimeType::class, [
                'label' => 'Duration - Warning collisions with other matches are not checked',
                'getter' => fn (MatchModel $match, FormInterface $form): int => DateTimeUtil::dateIntervalToSeconds($match->getDuration()),
                'setter' => function (MatchModel $match, int $duration, FormInterface $form): void {
                    $match->setDuration(new \DateInterval("PT{$duration}S"));
                },
                'widget' => 'single_text',
                'input' => 'timestamp',
                'html5' => true,
                'with_minutes' => true,
                'with_seconds' => true
            ]);

        $builder
            ->add('submit', SubmitType::class, ['label' => 'Add']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatchModel::class
        ]);
    }
}
