<?php

namespace App\Form;

use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as SFType;

class TournamentCreateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tournamentName',SFType\TextType::class)
            ->add('startDate',SFType\DateType::class)
            ->add('endDate',SFType\DateType::class)
            ->add('location',SFType\TextType::class)
            ->add('descritpion',SFType\TextType::class)
            ->add('maxParticipant',SFType\IntegerType::class)
            ->add('nomJeu',SFType\TextType::class)
            ->add('Create',SFType\SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournament::class,
        ]);
    }
}
