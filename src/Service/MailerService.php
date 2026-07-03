<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $from    = 'vite.gourmandbordeaux@gmail.com',
        private string $fromName = 'Vite et Gourmand',
    ) {}

    private function email(): Email
    {
        return (new Email())->from(sprintf('%s <%s>', $this->fromName, $this->from));
    }

    public function sendBienvenue(Utilisateur $user): void
    {
        $this->mailer->send(
            $this->email()
                ->to($user->getEmail())
                ->subject('Bienvenue chez Vite & Gourmand !')
                ->html(sprintf(
                    '<h1>Bonjour %s !</h1>
                     <p>Votre compte a été créé avec succès. Vous pouvez dès maintenant vous connecter et passer commande.</p>
                     <p>À très bientôt,<br><strong>L\'équipe Vite & Gourmand</strong></p>',
                    htmlspecialchars($user->getPrenom())
                ))
        );
    }

    public function sendConfirmationCommande(Commande $commande): void
    {
        $user = $commande->getUtilisateur();
        $this->mailer->send(
            $this->email()
                ->to($user->getEmail())
                ->subject('Confirmation de votre commande ' . $commande->getNumeroCommande())
                ->html(sprintf(
                    '<h1>Commande confirmée !</h1>
                     <p>Bonjour %s,</p>
                     <p>Votre commande <strong>%s</strong> a bien été reçue.</p>
                     <ul>
                         <li>Menu : %s</li>
                         <li>Date : %s</li>
                         <li>Personnes : %d</li>
                         <li>Total : %.2f€</li>
                     </ul>
                     <p>Merci pour votre confiance !<br><strong>L\'équipe Vite & Gourmand</strong></p>',
                    htmlspecialchars($user->getPrenom()),
                    $commande->getNumeroCommande(),
                    $commande->getMenu()->getTitre(),
                    $commande->getDatePrestation()->format('d/m/Y à H\hi'),
                    $commande->getNombrePersonnes(),
                    $commande->getPrixTotal()
                ))
        );
    }

    public function sendRetourMateriel(Commande $commande): void
    {
        $user = $commande->getUtilisateur();
        $this->mailer->send(
            $this->email()
                ->to($user->getEmail())
                ->subject('Retour de matériel — ' . $commande->getNumeroCommande())
                ->html(
                    '<h1>Retour de matériel requis</h1>
                     <p>Bonjour,</p>
                     <p>Du matériel a été prêté lors de votre prestation. Vous disposez de <strong>10 jours ouvrés</strong> pour le restituer.</p>
                     <p>Sans retour dans ce délai, des frais de <strong>600€</strong> seront appliqués conformément aux conditions générales de vente.</p>
                     <p>Pour organiser le retour, contactez-nous par e-mail ou par téléphone.</p>
                     <p>Cordialement,<br><strong>L\'équipe Vite & Gourmand</strong></p>'
                )
        );
    }

    public function sendCommandeTerminee(Commande $commande): void
    {
        $user = $commande->getUtilisateur();
        $this->mailer->send(
            $this->email()
                ->to($user->getEmail())
                ->subject('Votre avis nous intéresse — ' . $commande->getNumeroCommande())
                ->html(sprintf(
                    '<h1>Comment s\'est passée votre prestation ?</h1>
                     <p>Bonjour %s,</p>
                     <p>Votre commande <strong>%s</strong> est maintenant terminée.</p>
                     <p>Connectez-vous à votre espace pour laisser un avis et partager votre expérience.</p>
                     <p>Merci !<br><strong>L\'équipe Vite & Gourmand</strong></p>',
                    htmlspecialchars($user->getPrenom()),
                    $commande->getNumeroCommande()
                ))
        );
    }

    public function sendNouvelEmploye(Utilisateur $employe): void
    {
        $this->mailer->send(
            $this->email()
                ->to($employe->getEmail())
                ->subject('Votre compte employé Vite & Gourmand')
                ->html(sprintf(
                    '<h1>Bienvenue dans l\'équipe !</h1>
                     <p>Un compte employé a été créé pour vous.</p>
                     <p>Votre identifiant : <strong>%s</strong></p>
                     <p>Pour obtenir votre mot de passe, rapprochez-vous de l\'administrateur.</p>
                     <p>À bientôt !<br><strong>L\'équipe Vite & Gourmand</strong></p>',
                    htmlspecialchars($employe->getEmail())
                ))
        );
    }

    public function sendContact(string $email, string $titre, string $description): void
    {
        $this->mailer->send(
            $this->email()
                ->to('contact@vitegourmand.fr')
                ->replyTo($email)
                ->subject('[Contact] ' . $titre)
                ->html(sprintf(
                    '<h2>Nouveau message de contact</h2>
                     <p><strong>De :</strong> %s</p>
                     <p><strong>Titre :</strong> %s</p>
                     <p><strong>Message :</strong></p>
                     <p>%s</p>',
                    htmlspecialchars($email),
                    htmlspecialchars($titre),
                    nl2br(htmlspecialchars($description))
                ))
        );
    }

    public function sendResetPassword(Utilisateur $user, string $token): void
    {
        $resetUrl = 'https://vitegourmand33.vercel.app/#reinitialiser-mdp?token=' . $token;

        $this->mailer->send(
            $this->email()
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe — Vite & Gourmand')
                ->html(sprintf(
                    '<h2>Réinitialisation de mot de passe</h2>
                     <p>Bonjour %s,</p>
                     <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                     <p>Cliquez sur le lien ci-dessous pour choisir un nouveau mot de passe (valable 1 heure) :</p>
                     <p><a href="%s">Réinitialiser mon mot de passe</a></p>
                     <p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.</p>',
                    htmlspecialchars($user->getPrenom()),
                    htmlspecialchars($resetUrl)
                ))
        );
    }
}
