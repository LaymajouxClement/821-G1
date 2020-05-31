<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/inscription", name="registration")
     */
    public function index(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, UserAuthenticator $authenticator, \Swift_Mailer $mailer): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Encode le mot de passe r�cup�rer
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                    )
                );
            
            // On g�n�re un token et on l'enregistre
            $user->setActivationToken(md5(uniqid()));
            
            $user->setAccountActivation(false);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // On cr�e le message
            $message = (new \Swift_Message('Activation compte'))
                // On attribue l'exp�diteur
                ->setFrom('yoan.guiraud@lycee-bourdelle.fr')
                // On attribue le destinataire
                ->setTo($user->getEmail())
                // On cr�e le texte avec la vue
                ->setBody(
                    $this->renderView(
                        'emails/activation.html.twig', ['token' => $user->getActivationToken()]
                    ),
                    'text/html'
                )
            ;
            $mailer->send($message);
            
            // On g�n�re un message
            $this->addFlash('message', 'Un email a ete envoye a votre adresse mail pour verifier votre compte' );
            
            //return $this->redirectToRoute('login');
                
            // Connexion automatique apr�s avoir cr�� un compte
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
            
        }

        return $this->render('registration/register.html.twig', [
            'current_menu' => 'active_registration',
            'registrationForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/activation/{token}", name="activation")
     */
    public function activation($token, UserRepository $users)
    {
        // On recherche si un utilisateur avec ce token existe dans la base de donn�es
        $user = $users->findOneBy([ 'activation_token' => $token ]);
        
        // Si aucun utilisateur n'est associ� � ce token
        if(!$user){
            // On renvoie une erreur 404
            throw $this->createNotFoundException('Cet utilisateur existe pas');
        }

        // On supprime le token
        $user->setActivationToken(null);
        $user->setAccountActivation(true);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // On g�n�re un message
        $this->addFlash('message', 'Vous avez bien active votre compte !' );

        // On retourne � l'accueil
        return $this->redirectToRoute('login');
    }
}
