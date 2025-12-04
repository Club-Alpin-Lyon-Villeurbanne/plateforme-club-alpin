<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127141241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrates existing user profile photo from /ftp/user/{id}/xxx.jpg to MediaUpload entity';
    }

    public function up(Schema $schema): void
    {$filesystem = new Filesystem();

        // Déterminer le chemin du projet
        $projectDir = realpath(__DIR__ . '/..');

        // Get all articles
        $users = $this->connection->fetchAllAssociative('SELECT id_user FROM caf_user');

        $now = new \DateTimeImmutable();
        $timestamp = $now->format('Y-m-d H:i:s');
        $uploadDir = $projectDir . '/public/ftp/uploads/files';

        // Ensure upload directory exists
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir);
        }

        $this->write('Starting migration of user profile photos to MediaUpload entity');
        $migratedCount = 0;

        foreach ($users as $user) {
            $userId = $user['id_user'];

            // Check if the image file exists
            $oldImagePath = "/ftp/user/{$userId}/profil.jpg";
            $fullOldImagePath = $projectDir . '/public' . $oldImagePath;

            $sourceImagePath = null;
            $imagePath = null;

            // Check which image exists
            if ($filesystem->exists($fullOldImagePath)) {
                $imagePath = $fullOldImagePath;
                $sourceImagePath = $oldImagePath;
            }

            if ($imagePath) {
                // Generate a unique filename
                $newFilename = 'user_' . $userId . '_' . uniqid() . '.jpg';
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
                            "user_{$userId}_photo.jpg",
                            'image/jpeg',
                            $timestamp,
                            $userId,
                            true,
                        ]
                    );

                    // Update the article to reference the new MediaUpload using the unique filename
                    $this->addSql(
                        'UPDATE caf_user SET media_upload_id = (SELECT id FROM media_upload WHERE filename = ?) WHERE id_user = ?',
                        [$newFilename, $userId]
                    );

                    ++$migratedCount;
                    $this->write("Migrated image for user {$userId} from {$sourceImagePath} to MediaUpload path {$newFilePath}");
                } catch (\Exception $e) {
                    $this->write("Error migrating image for user {$userId}: " . $e->getMessage());
                }
            }
        }

        $this->write("Migration completed. Migrated {$migratedCount} user images.");
    }

    public function down(Schema $schema): void
    {
        $this->write('Cannot revert this migration as it would result in data loss');
    }
}
