**Boost Laravel Performance with TatTran Repository**

**What is it?**

The TatTran Repository is a Laravel package designed to **supercharge your app's speed** by **caching database queries and model operations**. This means your app can retrieve data **instantly**, leading to a **smoother user experience**.

**Key Features:**

* **Effortless Caching:** Cache any database query or model operation with ease.
* **Flexible Configuration:** Fine-tune cache times and define tags for granular control.
* **Automatic Tagging:** Tag each request with the `TagForRequestMiddleware` for more precise management.
* **Clear Cache with Ease:** Utilize the `query:cache-flush` command to clear all cached queries.
* **Automatic Cache Invalidation:** The `FlushCacheObserver` automatically flushes cache entries when models are created, updated, or deleted.
* **Simplified Cache Management:** Leverage the `ModelCacheTrait` for generating cache keys and managing cache times for your models.
* **Open Source:** Contribute to the package's growth and benefit from the community.

**Ready to Get Started?**

Installation is a breeze:

1. `composer require tattran22/repository`
2. `php artisan vendor:publish --provider="TatTran\Repository\RepositoryServiceProvider"`
3. Configure settings in `config/repository.php`

**Documentation and Support:**

For detailed usage instructions, configuration options, and contribution guidelines, head over to the project's GitHub repository: [link to repository].

**License:**

The TatTran Repository is open-source software released under the MIT license.
