<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LessonType extends AbstractType
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Название',
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'Содержание урока',
            ])
            ->add('serialNumber', NumberType::class, [
                'label' => 'Номер урока',
                'constraints' => [
                    new NotBlank(message: 'Номер урока не может быть пустым'),
                    new Range(
                        notInRangeMessage: 'Больше 1 000 и меньше 1 нельзя :(',
                        min: 1,
                        max: 10000,
                    ),
                ]
            ])
            ->add('course', EntityType::class, [
                'class' => Course::class,
                'label' => 'КУРС',
                'choice_label' => 'title',
            ])
        ;  

        // TODO решение должно полностью реализовываться через Transformer
        // Если параметр course_id передан, попытаемся получить курс с этим ID
        $request = $this->requestStack->getCurrentRequest();
        $courseId = $request->query->get('course_id');

        if ($courseId) {
            if($request->getMethod() == 'POST')
            {
                $builder->get('course')->addModelTransformer(new CourseToEntityTransformer($this->entityManager, $courseId));
            } else {
                $course = $this->entityManager->getRepository(Course::class)->find($courseId);

                if ($course) {
                    $builder->add('course', EntityType::class, [
                        'class' => Course::class,
                        'choice_label' => 'title',
                        'choice_value' => 'id',
                        'data' => $course,
                        'disabled' => true, // блокирую поле
                    ]);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class
        ]);
    }
}
