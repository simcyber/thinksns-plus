<?php

namespace Zhiyi\Plus\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Filesystem\FilesystemManager;
use Zhiyi\Plus\Contracts\Cdn\UrlFactory as CdnUrlFactoryContract;
use Zhiyi\Plus\Contracts\Model\ShouldAvatar as ShouldAvatarContract;

trait HasAvatar
{
    /**
     * avatar extensions.
     *
     * @var array
     */
    protected $avatar_extensions = ['svg', 'png', 'jpeg', 'gif', 'bmp'];

    /**
     * Avatar prefix.
     *
     * @var string
     */
    protected $avatar_prefix = 'avatars';

    /**
     * Bootstrap the trait.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    public static function bootHasAvatar()
    {
        if (! (new static) instanceof ShouldAvatarContract) {
            throw new \Exception(sprintf('使用"HasAvatar"性状必须实现"%s"契约', ShouldAvatarContract::class));
        }
    }

    /**
     * Get avatar,.
     *
     * @param int $size
     * @param string $prefix
     * @return mixed
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function avatar(int $size = 0, string $prefix = '')
    {
        $path = $this->avatarPath($prefix);

        if (! $path) {
            return null;
        }

        return app(CdnUrlFactoryContract::class)->generator()->url($path, $size ? [
            'width' => $size,
            'height' => $size,
        ] : []);
    }

    /**
     * Get avatar file path.
     *
     * @param string $prefix
     * @return string|null
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function avatarPath(string $prefix = '')
    {
        $path = $this->makeAvatarPath($prefix);
        $disk = $this->filesystem()->disk('public');

        foreach ($this->avatar_extensions as $extension) {
            if ($disk->exists($filename = $path.'.'.$extension)) {
                return $filename;
            }
        }

        return null;
    }

    /**
     * Store avatar.
     *
     * @param UploadedFile $avatar
     * @return string|false
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function storeAvatar(UploadedFile $avatar, string $prefix = '')
    {
        $extension = strtolower($avatar->extension());
        if (! in_array($extension, $this->avatar_extensions)) {
            throw new \Exception('保存的头像格式不符合要求');
        }

        $filename = $this->makeAvatarPath($prefix);
        $path = pathinfo($filename, PATHINFO_DIRNAME);
        $name = pathinfo($filename, PATHINFO_BASENAME).'.'.$extension;

        $disk = $this->filesystem()->disk('public');
        if ($disk->exists($filename)) {
            $disk->deleteDirectory($filename);
        }

        $disk->delete(array_reduce($this->avatar_extensions, function (array $collect, $extension) use ($filename) {
            $collect[] = $filename.'.'.$extension;

            return $collect;
        }, [$filename]));

        return $avatar->storeAs($path, $name, 'public');
    }

    /**
     * make avatar file path.
     *
     * @return string
     * @author Seven Du <shiweidu@outlook.com>
     */
    protected function makeAvatarPath(string $prefix = ''): string
    {
        $filename = strval($this->getAvatarKey());
        if (strlen($filename) < 11) {
            $filename = str_pad($filename, 11, '0', STR_PAD_LEFT);
        }

        return sprintf(
            '%s/%s/%s/%s/%s',
            $prefix ?: $this->avatar_prefix,
            substr($filename, 0, 3),
            substr($filename, 3, 3),
            substr($filename, 6, 3),
            substr($filename, 9)
        );
    }

    /**
     *  Get filesystem.
     *
     * @return \Illuminate\Filesystem\FilesystemManager
     * @author Seven Du <shiweidu@outlook.com>
     */
    protected function filesystem(): FilesystemManager
    {
        return app(FilesystemManager::class);
    }
}
