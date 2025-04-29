<?php

declare(strict_types=1);

use Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Carbon\Rector\FuncCall\DateFuncCallToCarbonRector;
use Rector\Carbon\Rector\FuncCall\TimeFuncCallToCarbonRector;
use Rector\Carbon\Rector\MethodCall\DateTimeMethodCallToCarbonRector;
use Rector\Carbon\Rector\New_\DateTimeInstanceToCarbonRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\BooleanAnd\RemoveUselessIsObjectCheckRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Class_\StaticToSelfStaticMethodCallOnFinalClassRector;
use Rector\CodeQuality\Rector\ClassConstFetch\ConvertStaticPrivateConstantToSelfRector;
use Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Expression\TernaryFalseExpressionToIfRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector;
use Rector\CodeQuality\Rector\FuncCall\CallUserFuncWithArrowFunctionToInlineRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FuncCall\InlineIsAInstanceOfRector;
use Rector\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector;
use Rector\CodeQuality\Rector\FuncCall\SetTypeToCastRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\CompleteMissingIfElseBracketRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector;
use Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector;
use Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector;
use Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector;
use Rector\CodeQuality\Rector\Switch_\SwitchTrueToIfRector;
use Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector;
use Rector\CodeQuality\Rector\Ternary\NumberCompareToMaxFuncCallRector;
use Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use RectorLaravel\Rector\Assign\CallOnAppArrayAccessToStandaloneAssignRector;
use RectorLaravel\Rector\Cast\DatabaseExpressionCastsToMethodCallRector;
use RectorLaravel\Rector\Class_\AddExtendsAnnotationToModelFactoriesRector;
use RectorLaravel\Rector\Class_\AnonymousMigrationsRector;
use RectorLaravel\Rector\Class_\CashierStripeOptionsToStripeRector;
use RectorLaravel\Rector\Class_\RemoveModelPropertyFromFactoriesRector;
use RectorLaravel\Rector\Class_\UnifyModelDatesWithCastsRector;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\Empty_\EmptyToBlankAndFilledFuncRector;
use RectorLaravel\Rector\Expr\AppEnvironmentComparisonToParameterRector;
use RectorLaravel\Rector\FuncCall\ArgumentFuncCallToMethodCallRector;
use RectorLaravel\Rector\FuncCall\DispatchNonShouldQueueToDispatchSyncRector;
use RectorLaravel\Rector\FuncCall\FactoryFuncCallToStaticCallRector;
use RectorLaravel\Rector\FuncCall\NowFuncWithStartOfDayMethodCallToTodayFuncRector;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\FuncCall\RemoveRedundantValueCallsRector;
use RectorLaravel\Rector\FuncCall\RemoveRedundantWithCallsRector;
use RectorLaravel\Rector\If_\AbortIfRector;
use RectorLaravel\Rector\If_\ReportIfRector;
use RectorLaravel\Rector\If_\ThrowIfRector;
use RectorLaravel\Rector\MethodCall\AssertStatusToAssertMethodRector;
use RectorLaravel\Rector\MethodCall\ChangeQueryWhereDateValueWithCarbonRector;
use RectorLaravel\Rector\MethodCall\DatabaseExpressionToStringToMethodCallRector;
use RectorLaravel\Rector\MethodCall\EloquentOrderByToLatestOrOldestRector;
use RectorLaravel\Rector\MethodCall\FactoryApplyingStatesRector;
use RectorLaravel\Rector\MethodCall\JsonCallToExplicitJsonCallRector;
use RectorLaravel\Rector\MethodCall\LumenRoutesStringActionToUsesArrayRector;
use RectorLaravel\Rector\MethodCall\LumenRoutesStringMiddlewareToArrayRector;
use RectorLaravel\Rector\MethodCall\RedirectBackToBackHelperRector;
use RectorLaravel\Rector\MethodCall\RedirectRouteToToRouteHelperRector;
use RectorLaravel\Rector\MethodCall\RefactorBlueprintGeometryColumnsRector;
use RectorLaravel\Rector\MethodCall\UseComponentPropertyWithinCommandsRector;
use RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector;
use RectorLaravel\Rector\Namespace_\FactoryDefinitionRector;
use RectorLaravel\Rector\New_\AddGuardToLoginEventRector;
use RectorLaravel\Rector\PropertyFetch\OptionalToNullsafeOperatorRector;
use RectorLaravel\Rector\PropertyFetch\ReplaceFakerInstanceWithHelperRector;
use RectorLaravel\Rector\StaticCall\DispatchToHelperFunctionsRector;
use RectorLaravel\Rector\StaticCall\EloquentMagicMethodToQueryBuilderRector;
use RectorLaravel\Rector\StaticCall\ReplaceAssertTimesSendWithAssertSentTimesRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        RemoveMethodCallParamRector::class,
        ReplaceArgumentDefaultValueRector::class,
        DateFuncCallToCarbonRector::class,
        DateTimeInstanceToCarbonRector::class,
        DateTimeMethodCallToCarbonRector::class,
        TimeFuncCallToCarbonRector::class,
        AbsolutizeRequireAndIncludePathRector::class,
        AndAssignsToSeparateLinesRector::class,
        ArrayKeyExistsTernaryThenValueToCoalescingRector::class,
        ArrayMergeOfNonArraysToSimpleArrayRector::class,
        BooleanNotIdenticalToNotIdenticalRector::class,
        CallUserFuncWithArrowFunctionToInlineRector::class,
        ChangeArrayPushToArrayAssignRector::class,
        CleanupUnneededNullsafeOperatorRector::class,
        CombineIfRector::class,
        CombinedAssignRector::class,
        CommonNotEqualRector::class,
        CompactToVariablesRector::class,
        ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
        CompleteMissingIfElseBracketRector::class,
        ConvertStaticPrivateConstantToSelfRector::class,
        ExplicitBoolCompareRector::class,
        ExplicitReturnNullRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        ForeachItemsAssignToEmptyArrayToAssignRector::class,
        ForeachToInArrayRector::class,
        InlineArrayReturnAssignRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        InlineIfToExplicitIfRector::class,
        InlineIsAInstanceOfRector::class,
        JoinStringConcatRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        LogicalToBooleanRector::class,
        NewStaticToNewSelfRector::class,
        NumberCompareToMaxFuncCallRector::class,
        OptionalParametersAfterRequiredRector::class,
        RemoveSoleValueSprintfRector::class,
        RemoveUselessIsObjectCheckRector::class,
        ReplaceMultipleBooleanNotRector::class,
        SetTypeToCastRector::class,
        ShortenElseIfRector::class,
        SimplifyArraySearchRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SimplifyConditionsRector::class,
        SimplifyDeMorganBinaryRector::class,
        SimplifyEmptyArrayCheckRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        SimplifyForeachToCoalescingRector::class,
        SimplifyFuncGetArgsCountRector::class,
        SimplifyIfElseToTernaryRector::class,
        SimplifyIfNotNullReturnRector::class,
        SimplifyIfNullableReturnRector::class,
        SimplifyIfReturnBoolRector::class,
        SimplifyInArrayValuesRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyStrposLowerRector::class,
        SimplifyTautologyTernaryRector::class,
        SimplifyUselessVariableRector::class,
        SingleInArrayToCompareRector::class,
        SingularSwitchToIfRector::class,
        StaticToSelfStaticMethodCallOnFinalClassRector::class,
        StrlenZeroToIdenticalEmptyStringRector::class,
        SwitchNegatedTernaryRector::class,
        SwitchTrueToIfRector::class,
        TernaryEmptyArrayArrayDimFetchToCoalesceRector::class,
        TernaryFalseExpressionToIfRector::class,
        UnnecessaryTernaryExpressionRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        UnwrapSprintfOneArgumentRector::class,
        ArraySpreadInsteadOfArrayMergeRector::class,
        AbortIfRector::class,
        AddExtendsAnnotationToModelFactoriesRector::class,
        AddGenericReturnTypeToRelationsRector::class,
        AddGuardToLoginEventRector::class,
        AnonymousMigrationsRector::class,
        AppEnvironmentComparisonToParameterRector::class,
        ArgumentFuncCallToMethodCallRector::class,
        AssertStatusToAssertMethodRector::class,
        CallOnAppArrayAccessToStandaloneAssignRector::class,
        CashierStripeOptionsToStripeRector::class,
        ChangeQueryWhereDateValueWithCarbonRector::class,
        DatabaseExpressionCastsToMethodCallRector::class,
        DatabaseExpressionToStringToMethodCallRector::class,
        DispatchNonShouldQueueToDispatchSyncRector::class,
        DispatchToHelperFunctionsRector::class,
        EloquentMagicMethodToQueryBuilderRector::class,
        EloquentOrderByToLatestOrOldestRector::class,
        EmptyToBlankAndFilledFuncRector::class,
        FactoryApplyingStatesRector::class,
        FactoryDefinitionRector::class,
        FactoryFuncCallToStaticCallRector::class,
        JsonCallToExplicitJsonCallRector::class,
        LumenRoutesStringActionToUsesArrayRector::class,
        LumenRoutesStringMiddlewareToArrayRector::class,
        NowFuncWithStartOfDayMethodCallToTodayFuncRector::class,
        OptionalToNullsafeOperatorRector::class,
        RedirectBackToBackHelperRector::class,
        RedirectRouteToToRouteHelperRector::class,
        RefactorBlueprintGeometryColumnsRector::class,
        RemoveDumpDataDeadCodeRector::class,
        RemoveModelPropertyFromFactoriesRector::class,
        RemoveRedundantValueCallsRector::class,
        RemoveRedundantWithCallsRector::class,
        ReplaceAssertTimesSendWithAssertSentTimesRector::class,
        ReplaceFakerInstanceWithHelperRector::class,
        ReportIfRector::class,
        ThrowIfRector::class,
        UnifyModelDatesWithCastsRector::class,
        UseComponentPropertyWithinCommandsRector::class,
        ValidationRuleArrayStringValueToArrayRector::class,
        SeparateMultiUseImportsRector::class,

    ])
    ->withSets([
        LaravelSetList::LARAVEL_110,
    ]);
