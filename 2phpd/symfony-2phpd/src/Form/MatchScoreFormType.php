<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as SFType;


class MatchScoreFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scorePlayer', SFType\IntegerType::class)
            ->add('Valider', SFType\SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
