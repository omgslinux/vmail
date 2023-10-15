<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {

        //+- obtener error de login si lo hubiere
        $error = $authenticationUtils->getLastAuthenticationError();
        // último nombre de usuario introducido por el usuario
        $lastUsername = $authenticationUtils->getLastUsername();
        //-- return $this->render('login/index.html.twig', [

        return $this->render('security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        return new RedirectResponse($this->generateUrl('dn_send_me_the_bundle_confirm', array(
                    'token' => $token
                    )));
    }
}
