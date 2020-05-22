<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request,\Swift_Mailer $mailer)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            // On cr�e le message
            $message = (new \Swift_Message('Nouveau contact'))
                // On attribue l'exp�diteur
                ->setFrom($contact['email'])
                // On attribue le destinataire
                ->setTo('votre@adresse.fr')
                // On cr�e le texte avec la vue
                ->setBody(
                    $this->renderView(
                        'emails/contact.html.twig', compact('contact')
                    ),
                    'text/html'
                )
            ;
            $mailer->send($message);

            $this->addFlash('message', 'Votre message a �t� transmis, nous vous r�pondrons dans les meilleurs d�lais.'); // Permet un message flash de renvoi
            return $this->redirectToRoute('home');
        }
        return $this->render( 'contact/index.html.twig', [
                            'current_menu' => 'active_contact',
                            'contactForm' => $form->createView() ]
                            
        );
    }
}
