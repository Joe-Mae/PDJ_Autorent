<?php

namespace App\Support;

/**
 * Les trois périmètres de l'administration, du plusrestricted au plus large.
 *
 * Les périmètres sont emboîtés : un super-admin fait tout ce qu'un gestionnaire
 * fait, un gestionnaire tout ce qu'un agent d'accueil fait. On les décrit donc
 * par un niveau croissant plutôt que par une liste de rôles autorisés — c'est
 * ce qui évite qu'une nouvelle route doive être ajoutée à trois endroits.
 *
 * Un seul endroit décrit la frontière entre ce que l'agent peut toucher et ce
 * qui engage l'agence : les tarifs, le parc et les réglages de l'entreprise.
 */
enum StaffTier: int
{
    /** Réservations, documents, paiements, retrait et retour au guichet. */
    case AgentAccueil = 1;

    /** Tout ce qui précède, plus le parc, les tarifs et les contenus du site. */
    case Gestionnaire = 2;

    /** Tout ce qui précède, plus les réglages qui engagent l'agence. */
    case SuperAdmin = 3;

    public static function fromRole(string $role): ?self
    {
        return match ($role) {
            'agent_accueil' => self::AgentAccueil,
            'gestionnaire' => self::Gestionnaire,
            'super_admin' => self::SuperAdmin,
            default => null,
        };
    }

    /** Le niveau le plus élevé parmi tous les rôles portés par l'utilisateur. */
    public static function highest(?iterable $roles): ?self
    {
        $niveau = null;

        foreach ($roles ?? [] as $role) {
            $tier = is_string($role) ? self::fromRole($role) : self::fromRole($role->name ?? '');

            if ($tier !== null && ($niveau === null || $tier->value > $niveau->value)) {
                $niveau = $tier;
            }
        }

        return $niveau;
    }

    /** L'utilisateur atteint-il au moins ce périmètre ? */
    public function isAtLeast(self $required): bool
    {
        return $this->value >= $required->value;
    }

    public static function tryFromRole(string $role): ?self
    {
        return self::fromRole($role);
    }

    /** Les libellés, pour l'interface. */
    public function label(string $locale = 'fr'): string
    {
        return match ($this) {
            self::AgentAccueil => $locale === 'en' ? 'Front desk' : 'Agent d\'accueil',
            self::Gestionnaire => $locale === 'en' ? 'Manager' : 'Gestionnaire',
            self::SuperAdmin => $locale === 'en' ? 'Super admin' : 'Super administrateur',
        };
    }
}
