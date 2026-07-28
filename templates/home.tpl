{extends file="base.tpl"}

{block name="title"}Home - My Blog{/block}

{block name="content"}
    {foreach from=$categories item=category}
        <section class="category-section">
            <h2>{$category.name}</h2>
            {if $category.description}
                <p>{$category.description}</p>
            {/if}

            <ul class="article-list">
                {foreach from=$category.articles item=article}
                    <li class="article-item">
                        {if $article.image}
                            <img src="{$article.image}" alt="{$article.name}">
                        {/if}
                        <div>
                            <h3><a href="index.php?page=article&amp;id={$article.id}">{$article.name}</a></h3>
                            <p>{$article.description}</p>
                            <p class="meta">{$article.created_at} &middot; {$article.views} views</p>
                        </div>
                    </li>
                {foreachelse}
                    <li>No articles in this category yet.</li>
                {/foreach}
            </ul>

            {if $category.total > 0}
                <a class="btn-all" href="index.php?page=category&amp;id={$category.id}">All articles ({$category.total})</a>
            {/if}
        </section>
    {foreachelse}
        <p>No categories yet.</p>
    {/foreach}
{/block}
