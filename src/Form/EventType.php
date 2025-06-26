<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\Evt;
use App\Entity\Groupe;
use App\Repository\CommissionRepository;
use App\Repository\UserAttrRepository;
use App\Service\ParticipantService;
use App\UserRights;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventType extends AbstractType
{
    public function __construct(
        protected UserAttrRepository $userAttrRepository,
        protected ParticipantService $participantService,
        protected CommissionRepository $commissionRepository,
        protected UserRights $userRights,
        protected string $club,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Evt $event */
        $event = $options['data'];
        $commission = $event->getCommission();
        $this->participantService->buildManagersLists($commission, $event);

        // timestamps to datetimes
        $eventStartDate = null;
        $eventEndDate = null;
        $eventJoinStartDate = null;
        if (!empty($event->getTsp())) {
            $eventStartDate = new \DateTime();
            $eventStartDate->setTimestamp($event->getTsp());
        }
        if (!empty($event->getTspEnd())) {
            $eventEndDate = new \DateTime();
            $eventEndDate->setTimestamp($event->getTspEnd());
        }
        if (!empty($event->getJoinStart())) {
            $eventJoinStartDate = new \DateTime();
            $eventJoinStartDate->setTimestamp($event->getJoinStart());
        }

        $appointment = $event->getRdv();
        if (empty($appointment)) {
            $appointment = '56 rue du 4 août 1789 Villeurbanne';
        }
        $lat = $event->getLat();
        if (empty($lat)) {
            $lat = '45.76476483029371';
        }
        $long = $event->getLong();
        if (empty($long)) {
            $long = '4.879565284189081';
        }

        $builder
            ->add('commission', EntityType::class, [
                'class' => Commission::class,
                'choices' => array_filter(
                    iterator_to_array($this->commissionRepository->findVisible()),
                    fn (Commission $commission) => $this->userRights->allowedOnCommission('evt_create', $commission),
                ),
                'label' => 'Sortie liée à la commission',
                'placeholder' => 'Choisissez une commission',
                'required' => true,
                'attr' => [
                    'class' => 'type1 wide',
                    'style' => 'width: 100%',
                ],
            ])
            ->add('groupe', EntityType::class, [
                'class' => Groupe::class,
                'query_builder' => function (EntityRepository $er) use ($commission) {
                    return $er->createQueryBuilder('g')
                        ->where('g.actif = 1')
                        ->andWhere('g.idCommission = :commission')
                        ->setParameters(['commission' => $commission])
                        ->orderBy('g.nom', 'ASC')
                    ;
                },
                'label' => 'Groupe concerné par cette sortie',
                'required' => false,
                'attr' => [
                    'class' => 'type1 wide',
                ],
            ])
            ->add('encadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getEncadrants()),
                'data' => $this->participantService->getCurrentEncadrants(),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('coencadrants', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getCoencadrants()),
                'data' => $this->participantService->getCurrentCoencadrants(),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('initiateurs', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getInitiateurs()),
                'data' => $this->participantService->getCurrentInitiateurs(),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('benevoles', ChoiceType::class, [
                'label' => false,
                'choices' => array_flip($this->participantService->getBenevoles()),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ex : Escalade du Grand Som',
                    'minlength' => 10,
                    'maxlength' => 100,
                    'class' => 'type1',
                    'style' => 'width:320px',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 10,
                        'max' => 100,
                    ]),
                ],
            ])
            ->add('massif', TextType::class, [
                'label' => 'Massif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : Chartreuse',
                    'class' => 'type2 wide',
                ],
            ])
            ->add('rdv', TextType::class, [
                'label' => 'Lieu de rendez-vous covoiturage',
                'help' => 'Ville et adresse du lieu de RDV pour vous rendre à la sortie. Ce champ permet de placer le marqueur sur la carte.',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : place Bellecour, les fontanettes',
                    'minlength' => 3,
                    'maxlength' => 200,
                    'class' => 'type2 wide',
                ],
                'data' => $appointment,
                'help_attr' => [
                    'class' => 'mini',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 3,
                        'max' => 200,
                    ]),
                ],
            ])
            ->add('lat', HiddenType::class, [
                'label' => false,
                'required' => false,
                'data' => $lat,
            ])
            ->add('long', HiddenType::class, [
                'label' => false,
                'required' => false,
                'data' => $long,
            ])
            ->add('eventStartDate', DateTimeType::class, [
                'label' => 'Date et heure de RDV / covoiturage',
                'required' => true,
                'mapped' => false,
                'data' => $eventStartDate,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'type2 wide',
                ],
            ])
            ->add('eventEndDate', DateTimeType::class, [
                'label' => 'Date et heure (estimée) de retour',
                'required' => true,
                'mapped' => false,
                'data' => $eventEndDate,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'type2 wide',
                ],
                'help' => 'Retour au point de RDV / covoiturage',
                'help_attr' => [
                    'class' => 'mini',
                ],
            ])
            ->add('tarif', NumberType::class, [
                'label' => 'Tarif',
                'attr' => [
                    'placeholder' => 'ex : 35.50',
                    'class' => 'type2',
                ],
                'required' => false,
            ])
            ->add('tarifDetail', TextareaType::class, [
                'label' => 'Détails des frais',
                'attr' => [
                    'placeholder' => 'ex : coût de chaque nuitée, coût du transport, coût des repas, date à laquelle les frais sont perdus (et lesquels)',
                    'class' => 'type2 wide',
                    'rows' => 5,
                ],
                'required' => false,
            ])
            ->add('ngensMax', NumberType::class, [
                'label' => 'Nombre maximum de personnes sur cette sortie (encadrement compris)',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ex : 8',
                    'class' => 'type2 small',
                ],
                'constraints' => [
                    new GreaterThan(0),
                ],
            ])
            ->add('joinMax', NumberType::class, [
                'label' => 'Inscriptions maximum via le formulaire internet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : 5',
                    'class' => 'type2 small',
                ],
                'constraints' => [
                    new GreaterThan(0),
                ],
            ])
            ->add('joinStartDate', DateTimeType::class, [
                'label' => 'Les inscriptions démarrent le',
                'required' => false,
                'mapped' => false,
                'data' => $eventJoinStartDate,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'type2 wide',
                ],
            ])
            ->add('difficulte', TextType::class, [
                'label' => 'Difficulté, niveau',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : PD, 5d+, exposé, ...',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('denivele', TextType::class, [
                'label' => 'Dénivelé positif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : 1200',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'help' => 'm',
                'help_attr' => [
                    'class' => 'mini',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('distance', TextType::class, [
                'label' => 'Distance',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex : 13.50',
                    'maxlength' => 50,
                    'class' => 'type2',
                ],
                'help' => 'km',
                'help_attr' => [
                    'class' => 'mini',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                    ]),
                ],
            ])
            ->add('matos', TextareaType::class, [
                'label' => 'Matériel nécessaire',
                'attr' => [
                    'placeholder' => 'ex : 10 Dégaines, 1 Baudrier, 1 Maillot de bain, ...',
                    'class' => 'type2 wide',
                    'rows' => 5,
                ],
                'required' => false,
            ])
            ->add('itineraire', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'type2 wide',
                    'rows' => 5,
                ],
            ])
            ->add('details_caches', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'ex : fichier de covoiturage, groupe WhatsApp ou canal de discussion, ...',
                    'class' => 'type2 wide',
                    'rows' => 8,
                ],
                'required' => false,
            ])
            ->add('stuff_list', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'choices' => $this->getStuffList(),
                'attr' => [
                    'placeholder' => '- Listes prédéfinies',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'class' => 'type2 wide tinymce',
                    'rows' => 15,
                    'style' => 'width:615px;',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('eventSave', SubmitType::class, [
                'label' => '<span class="blanc">&gt;</span> ENREGISTRER ET DEMANDER LA PUBLICATION',
                'label_html' => true,
                'attr' => [
                    'class' => 'biglink',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evt::class,
        ]);
    }

    protected function getStuffList(): array
    {
        /* @todo compléter les listes pour les autres clubs */
        return match ($this->club) {
            'lyon' => [
                '- Listes prédéfinies' => '',
                'Ski alpinisme' => 'Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos.  SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, skis, bâtons, peaux, couteaux. Casque conseillé',
                'Rando raquettes' => 'Carte du CAF Imprimée recto-verso, Vitale, Mutuelle. Sac à dos adapté à la randonnée raquettes (avec des sangles) et suffisamment grand pour contenir les vêtements de l\'activité extérieure (30 L) : fourrure polaire, goretex ou équivalent, sur-sac, bonnet, gants, lunettes de soleil (masque suivant météo), crème solaire, guêtres. Bâtons avec grosses rondelles de neige / Kit de sécurité - comprenant DVA, pelle et sonde - qui peut être prêté par le CAF contre participation aux frais et un chèque de caution de 350 €. Prévoir un jeu de piles de rechange pour le DVA. Crampons a minima forestiers (contacter l\'encadrant.e). COUVERTURE DE SURVIE OBLIGATOIRE. Pique-nique et boisson (thermos ou gourde ou autre). Raquettes adaptées à vos chaussures et réglées au préalable / Autres matériels suivant information de l\'encadrant.e / Chaussures de rechange pour la voiture (avec sac plastique). Pour le covoiturage : espèces ou autre moyen comme PAYLIB.',
                'Randonnée Montagne' => 'Carte du CAF Imprimée recto-verso, Vitale, Mutuelle. Sac à dos adapté à la randonnée et suffisamment grand pour contenir les vêtements de l\'activité extérieure : fourrure polaire, goretex ou équivalent, cape de pluie, sur-sac, gants, bonnet ou chapeau, pique-nique, boisson, lunettes de soleil et crème solaire. Chaussures de montagne avec une semelle crantée, bâtons. Crampons forestiers suivant période et avis encadrant.e. Prévoir chaussures de rechange pour la voiture (avec sac plastique). Autres matériels suivant information de l\'encadrant.e. Pour le covoiturage : espèces ou autre moyen comme PAYLIB.',
                'Bivouac' => 'Sac de couchage, tapis de sol, lampe de poche, briquet, gamelles, repas, tente',
                'Via ferrata' => "Casque, baudrier, longe de via ferrata, gants de jardinage, vêtements de sport, petit sac à dos, 1-2 litres d'eau, pique nique",
                'Spéléo' => "Vêtements de sport sales, pull en laine, bottes ou chaussures de marche, gants Mappa, 1 litre d'eau, pique nique, 4 piles rondes type LR 6 (vous les récupérez à la fin de la sortie)",
                'Camping' => "Sac de couchage (avec sac à viande), tapis de sol, popote (assiette + bol), gourde, couverts, lampe de poche (frontale c'est mieux), petit nécessaire de toilette",
                'Escalade SAE' => "Baudrier, assureur et mousqueton de sécurité, chaussons d'escalade, licence CAF à jour, gourde d'eau, vêtements adaptés à l'escalade, haut chaud (il peut faire froid quand on assure), chaussures fermées propres pour assurer, élastique pour attacher les cheveux, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour le baudrier, attention à ne pas dépasser la durée d'usage indiquée sur la notice constructeur. Dans tous les cas, cet équipement doit être mis au rebut au plus tard 10 ans après leur fabrication.",
                'Escalade SNE' => "Casque normé EN12492, baudrier, assureur avec son mousqueton de sécurité, longe dynamique cousue par le fabricant avec son mousqueton de sécurité, un jeu de minimum 7 dégaines, un machard avec son mousqueton de sécurité, chaussons d'escalade, licence CAF à jour, gourde d'eau et/ou thermos, encas, vêtements adaptés à l'escalade, haut chaud (il peut faire froid quand on assure), une membrane coupe-vent, chaussures fermées pour assurer, lunettes de soleil, crème solaire, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour les éléments textiles de vos équipements de sécurité (baudrier, longe dynamique, sangles de dégaines, machard, etc.), attention à ne pas dépasser la durée d'usage indiquée sur la notice constructeur. Dans tous les cas, ces équipements doivent être mis au rebut au plus tard 10 ans après leur fabrication.",
                'Escalade GV' => "Casque normé EN12492, baudrier, assureur double gorges avec son mousqueton de sécurité, longe double dynamique cousue par le fabricant avec deux mousquetons de sécurité, un jeu de minimum 7 dégaines, un machard avec son mousqueton de sécurité, un anneau de corde dynamique cousu de 1,2m pour trianguler le relais avec 3 mousquetons de sécurité, chaussons d'escalade, licence CAF à jour, petit sac à dos (20L max), gourde d'eau ou thermos, encas, vêtements adaptés à l'escalade, haut chaud (il peut faire froid quand on assure), une membrane coupe-vent, lunettes de soleil, crème solaire, frontale avec batterie, pharmacie personnelle et du chocolat pour les encadrant.e.s ! Note : pour les éléments textiles de vos équipements de sécurité (baudrier, longes, sangles de dégaines, machard, etc.), attention à ne pas dépasser la durée d'usage indiquée sur la notice constructeur. Dans tous les cas, ces équipements doivent être mis au rebut au plus tard 10 ans après leur fabrication.",
                'Affaires personnelles' => 'Carte CAF, vêtements pour activité extérieure, fourrure polaire, coupe-vent, casquette, lunettes de soleil, crème solaire, appareil photos',
                'Alpinisme' => 'Piolet, casque, baudrier, crampons, 3 mousquetons à vis, longe en corde dynamique (pas de sangle pour se vacher), une sangle de 120, 2 anneaux de cordelette pour machard, gourde, sac à dos (30 litres), chaussures à semelles rigides, lampe frontale, lunettes de soleil cat 4. Vetements : système 3 couches : veste, et pantalon gore-tex ou équivalent, t-shirt merinos, polaire, guêtres, gants (prévoir une paire de rechange), bonnet.',
                'Cascade de glace' => 'Une paire de piolets techniques, une paire de crampons techniques, grosses chaussures à tiges rigides, 2 voire 3 paires de gants (dont imperméables), veste imperméable, vêtements chauds, bonnet, thé chaud...',
                'Vélo de Montagne' => 'Casque, gants et protections, chaussures, eau et nourriture de course, une chambre à air, une pompe, démonte-pneus, un multi-tool, une attache rapide de chaine, une patte de dérailleur, et un VTT en bon état de fonctionnement: freins, pneus, transmission, serrages... Et savoir réparer les petites pannes!',
                'Snowboard rando' => 'Carte CAF, doudoune, frontale, gants rechange, bonnet rechange, lunettes de soleil, crème solaire, appareil photos. SANS OUBLIER : DVA, sonde, pelle qui peuvent être prêtés par le CAF contre participation aux frais, boots, splitboard, bâtons, peaux, couteaux, visserie de rechange. Casque recommandé',
                'Trail' => 'Frontale, veste coupe vent, couverture de survie, carte du CAF, de quoi boire, de quoi manger en cas de moins bien',
            ],
            'chambery' => [
                '- Listes prédéfinies' => '',
            ],
            'clermont' => [
                '- Listes prédéfinies' => '',
            ],
            default => [
                '- Listes prédéfinies' => '',
            ],
        };
    }
}
