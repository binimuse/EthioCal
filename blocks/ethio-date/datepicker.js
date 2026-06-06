/**
 * EthiopianDatePicker — block canvas component.
 *
 * Three modes:
 *   EC   – author picks year / month / day directly in the Ethiopian calendar.
 *          Day options update instantly via local leap-year logic (kenat algorithm).
 *          The GC equivalent is fetched async from /ethio-cal/v1/convert.
 *   GC   – author picks from a native Gregorian date input.
 *          REST gc-to-ec conversion is authoritative; result is committed to attrs.
 *   Today – year/month/day stored as 0; PHP render_callback resolves at runtime.
 *
 * All EC↔GC conversions that affect block attributes go through the REST
 * endpoint so the PHP conversion logic stays server-authoritative.
 */

import { useState, useEffect } from '@wordpress/element';
import { __ }                  from '@wordpress/i18n';
import {
    SelectControl,
    TextControl,
    Button,
    Spinner,
} from '@wordpress/components';
import apiFetch   from '@wordpress/api-fetch';
import { monthNames } from 'kenat'; // monthNames.english / monthNames.amharic

// ── Local calendar helpers (same algorithm as PHP EthiopianDate) ──────────────

/** Ethiopian leap year: year % 4 === 3. */
const isEcLeapYear = ( y ) => y % 4 === 3;

/** Days in an Ethiopian month (30 for months 1-12; 5 or 6 for Pagumé). */
const ecDaysInMonth = ( y, m ) =>
    m === 13 ? ( isEcLeapYear( y ) ? 6 : 5 ) : 30;

// ── Static lookup tables ───────────────────────────────────────────────────────

/** 1-indexed month options built from kenat's English month names. */
const EC_MONTH_OPTIONS = monthNames.english.map( ( name, i ) => ( {
    label: `${ name } (${ i + 1 })`,
    value: i + 1,
} ) );

function makeDayOptions( year, month ) {
    const max = ecDaysInMonth( year, month );
    return Array.from( { length: max }, ( _, i ) => ( {
        label: String( i + 1 ),
        value: i + 1,
    } ) );
}

// ── REST helpers ───────────────────────────────────────────────────────────────

function ecPath( y, m, d ) {
    const pad = ( n ) => String( n ).padStart( 2, '0' );
    return `/ethio-cal/v1/convert?date=${ y }-${ pad( m ) }-${ pad( d ) }&direction=ec-to-gc`;
}

function gcPath( dateStr ) {
    return `/ethio-cal/v1/convert?date=${ dateStr }&direction=gc-to-ec`;
}

// ── Component ─────────────────────────────────────────────────────────────────

export function EthiopianDatePicker( { attributes, setAttributes } ) {
    const { ethiopianYear, ethiopianMonth, ethiopianDay } = attributes;

    const [ mode,       setMode       ] = useState( 'ec' );
    // GC date string matching the stored EC attrs (shown as a label in EC mode)
    const [ gcEquiv,    setGcEquiv    ] = useState( '' );
    // Controlled value for the GC <input type="date">
    const [ gcInput,    setGcInput    ] = useState( '' );
    const [ converting, setConverting ] = useState( false );
    const [ error,      setError      ] = useState( null );

    const isToday  = ! ethiopianYear;
    const curYear  = ethiopianYear  || 2017;
    const curMonth = ethiopianMonth || 1;
    const curDay   = ethiopianDay   || 1;

    // ── Fetch GC equivalent whenever the stored EC date is a real date ────────
    useEffect( () => {
        if ( ! ethiopianYear ) {
            setGcEquiv( '' );
            setConverting( false );
            return;
        }
        let cancelled = false;
        setConverting( true );
        setError( null );
        apiFetch( { path: ecPath( curYear, curMonth, curDay ) } )
            .then( ( res ) => {
                if ( cancelled ) return;
                setGcEquiv( res.gregorian );
                setConverting( false );
            } )
            .catch( () => {
                if ( ! cancelled ) setConverting( false );
            } );
        return () => {
            cancelled = true;
            setConverting( false );
        };
    }, [ ethiopianYear, ethiopianMonth, ethiopianDay ] ); // eslint-disable-line

    // Pre-populate the GC input when switching into GC mode
    useEffect( () => {
        if ( mode === 'gc' && gcEquiv ) {
            setGcInput( gcEquiv );
        }
    }, [ mode ] ); // eslint-disable-line

    // ── EC mode handlers ──────────────────────────────────────────────────────

    function onYearChange( raw ) {
        const y = parseInt( raw, 10 ) || 0;
        if ( y > 0 ) {
            const maxDay = ecDaysInMonth( y, curMonth );
            setAttributes( {
                ethiopianYear: y,
                ethiopianDay:  Math.min( curDay, maxDay ),
            } );
        } else {
            // Empty field → switch to Today mode
            setAttributes( { ethiopianYear: 0, ethiopianMonth: 0, ethiopianDay: 0 } );
        }
    }

    function onMonthChange( raw ) {
        const m      = parseInt( raw, 10 );
        const maxDay = ecDaysInMonth( curYear, m );
        setAttributes( {
            ethiopianMonth: m,
            ethiopianDay:   Math.min( curDay, maxDay ),
        } );
    }

    function onDayChange( raw ) {
        setAttributes( { ethiopianDay: parseInt( raw, 10 ) } );
    }

    // ── GC mode handler ───────────────────────────────────────────────────────

    async function onGcInputChange( val ) {
        setGcInput( val );
        if ( ! val ) return;
        setConverting( true );
        setError( null );
        try {
            const res = await apiFetch( { path: gcPath( val ) } );
            setAttributes( {
                ethiopianYear:  res.ethiopian.year,
                ethiopianMonth: res.ethiopian.month,
                ethiopianDay:   res.ethiopian.day,
            } );
            setGcEquiv( val );
        } catch {
            setError( __( 'Could not convert date. Please try a different date.', 'ethio-cal' ) );
        } finally {
            setConverting( false );
        }
    }

    // ── Today handler ─────────────────────────────────────────────────────────

    function onTodayClick() {
        setAttributes( { ethiopianYear: 0, ethiopianMonth: 0, ethiopianDay: 0 } );
        setGcEquiv( '' );
        setGcInput( '' );
        setMode( 'ec' );
    }

    // ── Derived UI values ─────────────────────────────────────────────────────

    const dayOptions = makeDayOptions( curYear, curMonth );

    const pagumNote = curMonth === 13
        ? ( isEcLeapYear( curYear )
            ? __( 'Leap year — Pagumé has 6 days', 'ethio-cal' )
            : __( 'Non-leap year — Pagumé has 5 days', 'ethio-cal' ) )
        : null;

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <div className="ethio-cal-picker">

            { /* ── Mode toolbar ── */ }
            <div
                className="ethio-cal-picker__toolbar"
                role="group"
                aria-label={ __( 'Date input mode', 'ethio-cal' ) }
            >
                <Button
                    variant={ ! isToday && mode === 'ec' ? 'primary' : 'secondary' }
                    size="small"
                    onClick={ () => setMode( 'ec' ) }
                >
                    { __( 'Ethiopian (EC)', 'ethio-cal' ) }
                </Button>
                <Button
                    variant={ ! isToday && mode === 'gc' ? 'primary' : 'secondary' }
                    size="small"
                    onClick={ () => setMode( 'gc' ) }
                >
                    { __( 'Gregorian (GC)', 'ethio-cal' ) }
                </Button>
                <Button
                    variant={ isToday ? 'primary' : 'tertiary' }
                    size="small"
                    onClick={ onTodayClick }
                >
                    { __( 'Today', 'ethio-cal' ) }
                </Button>
            </div>

            { /* ── Error notice ── */ }
            { error && (
                <p className="ethio-cal-picker__error" role="alert">{ error }</p>
            ) }

            { /* ── Today mode ── */ }
            { isToday && (
                <p className="ethio-cal-picker__today-label">
                    <em>
                        { __( "Displays today's Ethiopian date at render time.", 'ethio-cal' ) }
                    </em>
                </p>
            ) }

            { /* ── EC input mode ── */ }
            { ! isToday && mode === 'ec' && (
                <div className="ethio-cal-picker__ec">
                    <TextControl
                        label={ __( 'Year (EC)', 'ethio-cal' ) }
                        type="number"
                        value={ ethiopianYear || '' }
                        min={ 1 }
                        max={ 9999 }
                        placeholder={ __( 'e.g. 2017', 'ethio-cal' ) }
                        onChange={ onYearChange }
                    />
                    <SelectControl
                        label={ __( 'Month', 'ethio-cal' ) }
                        value={ curMonth }
                        options={ EC_MONTH_OPTIONS }
                        onChange={ onMonthChange }
                        help={ pagumNote }
                    />
                    <SelectControl
                        label={ __( 'Day', 'ethio-cal' ) }
                        value={ curDay }
                        options={ dayOptions }
                        onChange={ onDayChange }
                    />
                    <p className="ethio-cal-picker__equiv" aria-live="polite">
                        { converting
                            ? <Spinner />
                            : gcEquiv && <>{ `= ${ gcEquiv }` } <abbr title={ __( 'Gregorian Calendar', 'ethio-cal' ) }>GC</abbr></>
                        }
                    </p>
                </div>
            ) }

            { /* ── GC input mode ── */ }
            { ! isToday && mode === 'gc' && (
                <div className="ethio-cal-picker__gc">
                    { /* Native date input styled to match WP Components */ }
                    <label
                        className="ethio-cal-picker__gc-label components-base-control__label"
                        htmlFor="ethio-cal-gc-date"
                    >
                        { __( 'Gregorian Date', 'ethio-cal' ) }
                    </label>
                    <input
                        id="ethio-cal-gc-date"
                        type="date"
                        className="ethio-cal-picker__gc-input components-text-control__input"
                        value={ gcInput }
                        min="1900-01-01"
                        max="2100-12-31"
                        onChange={ ( e ) => onGcInputChange( e.target.value ) }
                    />
                    <p className="ethio-cal-picker__equiv" aria-live="polite">
                        { converting
                            ? <Spinner />
                            : ethiopianYear > 0 && (
                                <>
                                    { `= ${ monthNames.english[ curMonth - 1 ] } ${ curDay }, ${ curYear }` }
                                    { ' ' }
                                    <abbr title={ __( 'Ethiopian Calendar', 'ethio-cal' ) }>EC</abbr>
                                </>
                            )
                        }
                    </p>
                </div>
            ) }

        </div>
    );
}
