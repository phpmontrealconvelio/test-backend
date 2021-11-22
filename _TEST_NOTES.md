# initial main commit

## NOTES

# Solution design
Dans l'idéal, cette classe sera transformée en service pour la facilité son changement futur, pour faciliter l'utilisabilité de plusieurs services (formatters) pours des besoins d'affaire différentes : mail, mobile, suivant le type de l'utilisateur ou de la nature de la transaction. Déveloper Un service provider qui permet d'implémenter les règles de sélection de ces formatters selon le cas.

Pour répondre SHORT & SWEET aux besins du test, deux pistes sont identifiées :

- Piste 1 : Créer une interface Renderable avec la signature des deux fonctions renderHtml et renderText et changer les classes existates (Quote, User...) pour implémenter cette classe et développer les fonctions qui manquent dans certaines classes. Utiliser un trait pour les classes incluant la fonctionnalité de remplacement qui sera utilisée par la classe TemplateManager.

- Piste 2 : Toucher uniquement à la casse TemplateManager et refaire la logique de remplacement d'une façon plus claire et plus efficace.

-> la piste retenue est la Num 2 : réduire l'impact sur les autres classes (régressions) et satisfaire plus rapidement les besoins d'affaire.

# Description de la solution
Utiliser les interpelations pour retourner les placeholders, faire un preg_match_all sur la template pour interpeler les fonctions.
