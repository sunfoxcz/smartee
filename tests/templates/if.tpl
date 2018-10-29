{if $name eq 'Fred'}
    Welcome Sir.
{elseif $name eq 'Wilma'}
    Welcome Ma'am.
{else}
    Welcome, whatever you are.
{/if}

{* an example with "or" logic *}
{if $name eq 'Fred' or $name eq 'Wilma'}
    ...
{/if}

{* same as above *}
{if $name == 'Fred' || $name == 'Wilma'}
    ...
{/if}

{* parenthesis are allowed *}
{if ( $amount < 0 or $amount > 1000 ) and $volume >= 50}
    ...
{/if}

{* you can also embed php function calls *}
{if count($array) gt 0}
    ...
{/if}

{* check for array. *}
{if is_array($array) }
    .....
{/if}

{* check for not null. *}
{if isset($array) }
    .....
{/if}
