<?php
namespace R301\Controleur;

use R301\Modele\Participation\FeuilleDeMatch;
use R301\Modele\Participation\Participation;
use R301\Modele\Participation\ParticipationDAO;
use R301\Modele\Participation\Performance;
use R301\Modele\Participation\Poste;
use R301\Modele\Participation\TitulaireOuRemplacant;

class ParticipationControleur {
    private static ?ParticipationControleur $instance = null;
    private readonly ParticipationDAO $participations;
    private readonly JoueurControleur $joueurs;
    private readonly RencontreControleur $rencontres;

    private function __construct(JoueurControleur $joueurs) {
        $this->joueurs = $joueurs;
        $this->participations = ParticipationDAO::getInstance();
        $this->rencontres = RencontreControleur::getInstance();
    }

    public static function getInstance(): ParticipationControleur {
        if (self::$instance == null) {
            self::$instance = new ParticipationControleur(JoueurControleur::getInstance());
        }
        return self::$instance;
    }

    //Cette méthode permet de briser la dépendance cyclique entre les deux controleurs
    public static function getInstanceFromJoueurControleur(JoueurControleur $joueurs): ParticipationControleur {
        if (self::$instance == null) {
            self::$instance = new ParticipationControleur($joueurs);
        }
        return self::$instance;
    }

    public function lejoueurEstDejaSurLaFeuilleDeMatch(int $rencontreId, int $joueurId) : bool {
        return $this->participations->lejoueurEstDejaSurLaFeuilleDeMatch($rencontreId, $joueurId);
    }

    public function listerToutesLesParticipations() : array {
        return $this->participations->selectAllParticipations();
    }

    public function getFeuilleDeMatch(int $rencontreId) : FeuilleDeMatch {
        return new FeuilleDeMatch($this->participations->selectParticipationsByRencontreId($rencontreId));
    }

    public function assignerUnParticipant(
        int $joueurId,
        int $rencontreId,
        Poste $poste,
        TitulaireOuRemplacant $titulaireOuRemplacant
    ) : bool {
        $rencontre = $this->rencontres->getRenconterById($rencontreId);
        if ($rencontre->estPassee()) {
            return false;
        }

        $joueur = $this->joueurs->getJoueurById($joueurId);
        if ($joueur->getStatut() !== \R301\Modele\Joueur\JoueurStatut::ACTIF) {
            return false;
        }

        if ($this->participations->lePosteEstDejaOccupe($rencontreId, $poste, $titulaireOuRemplacant)
            || $this->lejoueurEstDejaSurLaFeuilleDeMatch($rencontreId, $joueurId)
        ) {
            return false;
        } else {
            $participationACreer = new Participation(
                0,
                $joueur,
                $rencontre,
                $titulaireOuRemplacant,
                null,
                $poste
            );

            return $this->participations->insertParticipation($participationACreer);
        }
    }

    public function modifierParticipation(
        int $participationId,
        Poste $poste,
        TitulaireOuRemplacant $titulaireOuRemplacant,
        int $joueurId
    ) : bool {
        $participationAModifier = $this->participations->selectParticipationById($participationId);

        if ($participationAModifier->getRencontre()->estPassee()) {
            return false;
        }

        $nouveauJoueur = $this->joueurs->getJoueurById($joueurId);
        if ($nouveauJoueur->getStatut() !== \R301\Modele\Joueur\JoueurStatut::ACTIF) {
            return false;
        }

        foreach ($this->participations->selectParticipationsByRencontreId($participationAModifier->getRencontre()->getRencontreId()) as $participation) {
            if (
                $participation->getParticipationId() !== $participationAModifier->getParticipationId()
                && $participation->getPoste() === $poste
                && $participation->getTitulaireOuRemplacant() === $titulaireOuRemplacant
            ) {
                return false;
            }

            if (
                $participation->getParticipationId() !== $participationAModifier->getParticipationId()
                && $participation->getParticipant()->getJoueurId() === $joueurId
            ) {
                return false;
            }
        }

        if ($participationAModifier->getParticipant()->getJoueurId() != $joueurId) {
            $participationAModifier->setParticipant($nouveauJoueur);
        }

        $participationAModifier->setPoste($poste);
        $participationAModifier->setTitulaireOuRemplacant($titulaireOuRemplacant);

        return $this->participations->updateParticipation($participationAModifier);
    }

    public function supprimerLaParticipation(int $participationId) : bool {
        $participationASupprimer = $this->participations->selectParticipationById($participationId);
        if ($participationASupprimer->getRencontre()->estPassee()) {
            return false;
        }

        return $this->participations->deleteParticipation($participationId);
    }

    public function mettreAJourLaPerformance(
        int $participationId,
        string $performance
    ) : bool {
        $participationAEvaluer = $this->participations->selectParticipationById($participationId);

        if (!$participationAEvaluer->getRencontre()->estPassee()) {
            return false;
        }

        $participationAEvaluer->setPerformance(Performance::fromName($performance));
        return $this->participations->updatePerformance($participationAEvaluer);
    }

    public function supprimerLaPerformance(int $participationId) : bool {
        $participationAEvaluer = $this->participations->selectParticipationById($participationId);

        if (!$participationAEvaluer->getRencontre()->estPassee()) {
            return false;
        }

        $participationAEvaluer->setPerformance(null);
        return $this->participations->updatePerformance($participationAEvaluer);
    }
}