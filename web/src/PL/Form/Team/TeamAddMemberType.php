<?php

namespace App\PL\Form\Team;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TeamAddMemberType extends AbstractType
{
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('members', TextType::class, [
                'autocomplete' => true,
                'autocomplete_url' => $this->router->generate('get_people', $options['find_url']),
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Search person'
                ],
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                    'delimiter' => ' ',
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Add']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'find_url' => []
        ]);
    }
}
