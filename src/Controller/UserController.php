<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserController extends AbstractController
{
    #[Route('/signup', name: 'signup')]
    public function registration(
        EntityManagerInterface $entityManager,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): Response {
        $formBuilder = $this->createFormBuilder()
            ->add('Username', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(min: 4),
                ],
            ])
            ->add('Password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => [
                    'label' => 'Password',
                    'constraints' => [
                        new NotBlank(),
                    ],
                ],
                'second_options' => ['label' => 'Repeat Password'],
            ])
            ->add('Email', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Submit']);

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $user = new User(
                username: $data['Username'],
                email: $data['Email'],
            );
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $data['Password'],
                ),
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $security->login($user);

            return $this->redirectToRoute('main');
        }

        return $this->render('user/signup.html.twig', [
            'form' => $form,
            'is_submitted' => $form->isSubmitted(),
            'data' => $data ?? null,
        ]);
    }

    #[Route('/signin', name: 'signin')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $formBuilder = $this->createFormBuilder()
            ->add('Username', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('Password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Submit']);

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user/signin.html.twig', [
            'form' => $formBuilder->getForm(),
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[Route('/', name: 'main')]
    public function main(Security $security, EntityManagerInterface $entityManager): Response
    {
        $currentUsername = $security->getUser();
        $currentUsername = $currentUsername->getUserIdentifier();
        $data = $entityManager->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        return $this->render(
            'user/main.html.twig',
            [
                'currentUsername' => $currentUsername,
                'data' => $data,
            ],
        );
    }

    #[Route('/block', name: 'user_block')]
    public function block(Request $request, UserRepository $userRepository): Response
    {
        $ids = array_map(
            fn(string $id) => Uuid::fromString($id),
            json_decode($request->getContent(), true),
        );

        $userRepository->blockMultiple($ids);

        return $this->redirectToRoute('signin');
    }

    #[Route('/unblock', name: 'user_unblock')]
    public function unblock(Request $request, UserRepository $userRepository): Response
    {
        $ids = array_map(
            fn(string $id) => Uuid::fromString($id),
            json_decode($request->getContent(), true),
        );

        $userRepository->unblockMultiple($ids);

        return new JsonResponse([]);
    }

    #[Route('/delete', name: 'user_delete')]
    public function delete(Request $request, UserRepository $userRepository): Response
    {
        $ids = array_map(
            fn(string $id) => Uuid::fromString($id),
            json_decode($request->getContent(), true),
        );

        $userRepository->deleteMultiple($ids);

        return new JsonResponse([]);
    }
}
