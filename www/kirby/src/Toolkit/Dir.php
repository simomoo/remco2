<?php

namespace Kirby\Toolkit;

use Exception;

/**
 * Low level directory handling utilities
 */
class Dir
{

    /**
     * Ignore when scanning directories
     *
     * @var array
     */
    public static $ignore = [
        '.',
        '..',
        '.DS_Store',
        '.gitignore',
        '.git',
        '.svn',
        '.htaccess',
        'Thumb.db',
        '@eaDir'
    ];

    /**
     * Copy the directory to a new destination
     *
     * @param string $dir
     * @param string $target
     * @return bool
     */
    public static function copy(string $dir, string $target): bool
    {
        if (is_dir($dir) === false) {
            throw new Exception('The directory "' . $dir . '" does not exist');
        }

        if (is_dir($target) === true) {
            throw new Exception('The target directory "' . $target . '" exists');
        }

        if (static::make($target) !== true) {
            throw new Exception('The target directory "' . $target . '" could not be created');
        }

        foreach (static::read($dir) as $name) {
            $root = $dir . '/' . $name;

            if (is_dir($root) === true) {
                static::copy($root, $target . '/' . $name);
            } else {
                F::copy($root, $target . '/' . $name);
            }
        }

        return true;
    }

    /**
     * Get all subdirectories
     *
     * @param string $dir
     * @return array
     */
    public static function dirs(string $dir, array $ignore = null): array
    {
        return array_filter(static::read($dir, $ignore), 'is_dir');
    }

    /**
     * Get all files
     *
     * @param string $dir
     * @return array
     */
    public static function files(string $dir, array $ignore = null): array
    {
        return array_filter(static::read($dir, $ignore), 'is_file');
    }

    /**
     * Read the directory and all subdirectories
     *
     * @param string $dir
     * @param array $ignore
     * @return array
     */
    public static function index(string $dir, bool $recursive = false, array $ignore = null, string $path = null)
    {
        $result = [];
        $dir    = realpath($dir);
        $items  = static::read($dir);

        foreach ($items as $item) {
            $root     = $dir . '/' . $item;
            $entry    = $path !== null ? $path . '/' . $item: $item;
            $result[] = $entry;

            if ($recursive === true && is_dir($root) === true) {
                $result = array_merge($result, static::index($root, true, $ignore, $entry));
            }
        }

        return $result;
    }

    /**
     * Checks if the folder has any contents
     *
     * @return boolean
     */
    public static function isEmpty(string $dir): bool
    {
        return count(static::read($dir)) === 0;
    }

    /**
     * Checks if the directory is readable
     *
     * @param string $dir
     * @return boolean
     */
    public static function isReadable(string $dir): bool
    {
        return is_readable($dir);
    }

    /**
     * Checks if the directory is writable
     *
     * @param string $dir
     * @return boolean
     */
    public static function isWritable(string $dir): bool
    {
        return is_writable($dir);
    }

    /**
     * Create a (symbolic) link to a directory
     *
     * @param string $source
     * @param string $link
     * @return boolean
     */
    public static function link(string $source, string $link): bool
    {
        Dir::make(dirname($link), true);

        if (is_dir($link) === true) {
            return true;
        }

        if (is_dir($source) === false) {
            throw new Exception(sprintf('The directory "%s" does not exist and cannot be linked', $source));
        }

        return symlink($source, $link);
    }

    /**
     * Creates a new directory
     *
     * @param   string  $dir The path for the new directory
     * @param   boolean $recursive Create all parent directories, which don't exist
     * @return  boolean True: the dir has been created, false: creating failed
     */
    public static function make(string $dir, bool $recursive = true): bool
    {
        if (empty($dir) === true) {
            return false;
        }

        if (is_dir($dir) === true) {
            return true;
        }

        $parent = dirname($dir);

        if ($recursive === true) {
            if (is_dir($parent) === false) {
                static::make($parent, true);
            }
        }

        if (is_writable($parent) === false) {
            throw new Exception(sprintf('The directory "%s" cannot be created', $dir));
        }

        return mkdir($dir);
    }

    /**
     * Recursively check when the dir and all
     * subfolders have been modified for the last time.
     *
     * @param   string   $dir The path of the directory
     * @param   string   $format
     * @param   string   $handler
     * @return  int
     */
    public static function modified(string $dir, string $format = null, string $handler = 'date')
    {
        $modified = filemtime($dir);
        $items    = static::read($dir);

        foreach ($items as $item) {
            if (is_file($dir . '/' . $item) === true) {
                $newModified = filemtime($dir . '/' . $item);
            } else {
                $newModified = static::modified($dir . '/' . $item);
            }

            $modified = ($newModified > $modified) ? $newModified : $modified;
        }

        return $format !== null ? $handler($format, $modified) : $modified;
    }

    /**
     * Moves a directory to a new location
     *
     * @param   string  $old The current path of the directory
     * @param   string  $new The desired path where the dir should be moved to
     * @return  boolean true: the directory has been moved, false: moving failed
     */
    public static function move(string $old, string $new): bool
    {
        if ($old === $new) {
            return true;
        }

        if (is_dir($old) === false || is_dir($new) === true) {
            return false;
        }

        if (static::make(dirname($new), true) !== true) {
            throw new Exception('The parent directory cannot be created');
        }

        return rename($old, $new);
    }

    /**
     * Returns a nicely formatted size of all the contents of the folder
     *
     * @param string $dir The path of the directory
     * @return mixed
     */
    public static function niceSize(string $dir)
    {
        return F::niceSize(static::size($dir));
    }

    /**
     * Reads all files from a directory and returns them as an array.
     * It skips unwanted invisible stuff.
     *
     * @param   string  $dir The path of directory
     * @param   array   $ignore Optional array with filenames, which should be ignored
     * @return  array   An array of filenames
     */
    public static function read(string $dir, array $ignore = null): array
    {
        if (is_dir($dir) === false) {
            return [];
        }

        return (array)array_diff(scandir($dir), $ignore ?? static::$ignore);
    }

    /**
     * Removes a folder including all containing files and folders
     *
     * @param string $dir
     * @return boolean
     */
    public static function remove(string $dir): bool
    {
        $dir = realpath($dir);

        if (is_dir($dir) === false) {
            return true;
        }

        if (is_link($dir) === true) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $childName) {
            if (in_array($childName, ['.', '..']) === true) {
                continue;
            }

            $child = $dir . '/' . $childName;

            if (is_link($child) === true) {
                unlink($child);
            } elseif (is_dir($child) === true) {
                static::remove($child);
            } else {
                F::remove($child);
            }
        }

        return rmdir($dir);
    }

    /**
     * Gets the size of the directory and all subfolders and files
     *
     * @param   string $dir The path of the directory
     * @return  mixed
     */
    public static function size(string $dir)
    {
        if (is_dir($dir) === false) {
            return false;
        }

        $size  = 0;
        $items = static::read($dir);

        foreach ($items as $item) {
            $root = $dir . '/' . $item;

            if (is_dir($root) === true) {
                $size += static::size($root);
            } elseif (is_file($root) === true) {
                $size += F::size($root);
            }
        }

        return $size;



        // It's easier to handle this with the Folder class
        $object = new Folder($dir);
        return $object->size();
    }

    /**
     * Checks if the directory or any subdirectory has been
     * modified after the given timestamp
     *
     * @param string $dir
     * @param int $time
     * @return boolean
     */
    public static function wasModifiedAfter(string $dir, int $time): boolean
    {
        if (filemtime($dir) > $time) {
            return true;
        }

        $content = static::read($dir);

        foreach ($content as $item) {
            $subdir = $dir . '/' . $item;

            if (filemtime($subdir) > $time) {
                return true;
            }

            if (is_dir($subdir) === true && static::wasModifiedAfter($subdir, $time) === true) {
                return true;
            }
        }

        return false;
    }
}
