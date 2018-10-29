{capture}
    {$aaa}
{/capture}
{$smarty.capture.default}

{capture name=capture2}
    {$bbb}
{/capture}
{$smarty.capture.capture2}

{capture assign=capture3}
    {$ccc}
{/capture}
{$capture3}
