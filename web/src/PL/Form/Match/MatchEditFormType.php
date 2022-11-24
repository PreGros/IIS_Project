<?php

namespace App\PL\Form\Match;

use App\BL\Match\MatchModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('points_first', NumberType::class, [
                'label' => 'Points for',
                'getter' => fn (MatchModel $match, FormInterface $form): ?float => $match->getParticipant1()?->getPoints(),
                'setter' => function (MatchModel $match, float $points_first, FormInterface $form): void {
                    $match->getParticipant1()?->setPoints($points_first);
                }
            ])
            ->add('duration', NumberType::class, [
                'label' => 'Points for',
                'getter' => fn (MatchModel $match, FormInterface $form): ?float => $match->getParticipant2()?->getPoints(),
                'setter' => function (MatchModel $match, float $points_second, FormInterface $form): void {
                    $match->getParticipant2()?->setPoints($points_second);
                }
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
