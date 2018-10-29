{foreach from=$firstArray item=item}
    {$item}
{/foreach}

{foreach from=$secondArray item=item key=k}
    {$k}={$item}
{/foreach}

{foreach from=$secondArray item=item key=k name=foo}
    {$smarty.foreach.foo.index}
    {$smarty.foreach.foo.iteration}
    {$smarty.foreach.foo.total}
    {$k}
    {$item}
{/foreach}

{foreach from=$thirdArray item=item}
    {$item.one}
    {$item.two}
{/foreach}

{foreach from=$emptyArray item=item}
{foreachelse}
    no items
{/foreach}
