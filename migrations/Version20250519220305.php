<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519220305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate existing article attachments from /ftp/articles/{id}/figure.jpg to MediaUpload entities';
    }

    public function up(Schema $schema): void
    {
        $filesystem = new Filesystem();

        // Déterminer le chemin du projet
        $projectDir = realpath(__DIR__ . '/..');

        // Get all articles
        $articles = $this->connection->fetchAllAssociative('SELECT id_article, user_article FROM caf_article');

        $now = new \DateTimeImmutable();
        $timestamp = $now->format('Y-m-d H:i:s');
        $uploadDir = $projectDir . '/public/uploads/files';

        // Ensure upload directory exists
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir);
        }

        $this->write('Starting migration of article images to MediaUpload entities');
        $migratedCount = 0;

        foreach ($articles as $article) {
            $articleId = $article['id_article'];
            $userId = $article['user_article'];

            // Check if the image file exists
            $oldImagePath = "/ftp/articles/{$articleId}/figure.jpg";

            $fullOldImagePath = $projectDir . '/public' . $oldImagePath;

            $imagePath = null;

            // Check which image exists
            if ($filesystem->exists($fullOldImagePath)) {
                $imagePath = $fullOldImagePath;
                $sourceImagePath = $oldImagePath;
            }

            if ($imagePath) {
                // Generate a unique filename
                $newFilename = 'article_' . $articleId . '_' . uniqid() . '.jpg';
                $newFilePath = $uploadDir . '/' . $newFilename;

                // Copy the file to the new location
                try {
                    $filesystem->copy($imagePath, $newFilePath, true);

                    // Create a new MediaUpload entry with the unique filename
                    $this->addSql(
                        'INSERT INTO media_upload
                        (filename, original_filename, mime_type, created_at, uploaded_by_id, used)
                        VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $newFilename,
                            "article_{$articleId}_image.jpg",
                            'image/jpeg',
                            $timestamp,
                            $userId,
                            true,
                        ]
                    );

                    // Update the article to reference the new MediaUpload using the unique filename
                    $this->addSql(
                        'UPDATE caf_article SET media_upload_id = (SELECT id FROM media_upload WHERE filename = ?) WHERE id_article = ?',
                        [$newFilename, $articleId]
                    );

                    ++$migratedCount;
                    $this->write("Migrated image for article {$articleId} from {$sourceImagePath} to MediaUpload with filename {$uniqueIdentifier}");
                } catch (\Exception $e) {
                    $this->write("Error migrating image for article {$articleId}: " . $e->getMessage());
                }
            }
        }

        $this->write("Migration completed. Migrated {$migratedCount} article images.");
    }

    public function down(Schema $schema): void
    {
        $this->write('Cannot revert this migration as it would result in data loss');
    }
}
