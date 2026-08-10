<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration(); ?>
{
    public function getId(): string
    {
        return '<?= $id; ?>';
    }

    public function getAttack(): int
    {
        return $this->getValue(self::ATTACK, true);
    }

    public function play(GameContext $context, array $data = []): void
    {
        //@ŧodo
    }
}
