<?php

class Tritas_HTML_Tidy
{
	public function __construct()
	{
		
	}
	
	// This output filter uses the nice html tidy function from the MY_Loader example 
	// at http://codeigniter.com/wiki/HTML_auto-indenting_view_loader/
	// I only didn't use php's html tidy function because MAMP's php doesn't have it 
	// for some stupid reason :/.
	// I modified the function slightly to preserve the contents of 
	// <pre> tags, as they really shouldn't be tidied, they're there specifically 
	// to preserve the formatting that's specified by the html coder. 
	
	public static function tidy( $html )
	{
		// Get all the pre tags and their contents into an array.
		$pre_tag_pattern = "~<pre\b[^>]*>.*?</pre>~s";
		$pre_tag_matches = array();
		preg_match_all( $pre_tag_pattern, $html, $pre_tag_matches );
		// We only want the full matches, the next line gets just those.
		$pre_tag_matches = $pre_tag_matches[0];
		
		// Replace all pre tags in $html with an identifying placeholder of the 
		// form <:pre_tag_match_1:>. Save each placeholder in an array so we can
		// use them for a str_replace() along with the $pre_tag_matches in a bit.
		$placeholders = array();
		for( $i = 0; $i < count($pre_tag_matches); $i++ )
		{
			$placeholder = "<:pre_tag_match_$i:>";
			$html = preg_replace( $pre_tag_pattern, $placeholder, $html, 1);
			$placeholders[] = $placeholder;
		}
		
		// Tidy the remaining html.
		$html = self::html_tidy_clean_html_code( $html );
		
		// Replace the placeholders with the original pre tags.
		$html = str_replace($placeholders, $pre_tag_matches, $html);
		
		return $html;
	}
	
	private static function html_tidy_clean_html_code( $uncleanhtml )
	{
		// Set wanted indentation
		$indent = "\t";
	
		// Set tags that should not indent their contents.
		$no_indent = array( 'html', 'head', 'body', 'script' );
	
		// Set tags that should not linebreak
		$no_linebreak = array
		( 
			'a', 'b', 'em', 'h1', 'h2', 'h3', 'h4', 
			'h5', 'h6', 'i', 'span', 'strong', 'title', 
			'textarea', 'option', 'legend', 'label', 'th' 
		);
		
		// The first thing to do is to remove *all* new lines and tabs O_O !
		$uncleanhtml = str_replace( "\n", "", $uncleanhtml );
		$uncleanhtml = str_replace( "\t", "", $uncleanhtml );
		$uncleanhtml = str_replace( "  ", " ", $uncleanhtml );
		
		//return $uncleanhtml;
		
		/* STRIP SUPERFLUOUS WHITESPACE */
		
		// Remove all indentation
		$uncleanhtml = preg_replace( "/[\r\n]+[\s\t]+/", "\n", $uncleanhtml );
	
		// Remove all trailing space
		$uncleanhtml = preg_replace( "/[\s\t]+[\r\n]+/", "\n", $uncleanhtml );
	
		// Remove all blank lines
		$uncleanhtml = preg_replace( "/[\r\n]+/", "\n", $uncleanhtml );
	
	
	// Alternative method - forces newlines all over. Messier but more consistent
	
	// Insert newlines around all tags
		$fixed_uncleanhtml = preg_replace("/(<[^>]+>)/", "\n\${1}\n", $uncleanhtml);
	// Remove newlines between 'whitespace-adjacent' pairs
		$fixed_uncleanhtml = preg_replace("/((<[a-zA-Z]>)|(<[^\/][^>]*[^\/>]>))[\n\s\t]+(<\/)/U", "\${1}\${4}", $fixed_uncleanhtml);
	// Remove all blank lines
		$fixed_uncleanhtml = preg_replace("/[\r\n]+/", "\n", $fixed_uncleanhtml);
	// Remove newlines after opening tags from our no_linebreak list (unless they are self-closing)
		$fixed_uncleanhtml = preg_replace("/(<(".implode('|', $no_linebreak).")((\s*>)|(\s[^>]*[^\/]>)))\n+/U", "\${1}", $fixed_uncleanhtml);
	// Remove newlines before closing tags from our no_linebreak list
		$fixed_uncleanhtml = preg_replace("/\n+(<\/(".implode('|', $no_linebreak).")[\s\t]*>)/U", "\${1}", $fixed_uncleanhtml);
	
	
		/* INSERT LINE SEPARATORS */
	/*
		// Separate 'whitespace-adjacent' tags with newlines, unless they are a pair
		$fixed_uncleanhtml = preg_replace( "/>[\s\t]*</", ">\n<", $uncleanhtml );
		$fixed_uncleanhtml = preg_replace
		(
			"/((<[a-zA-Z]>)|(<[^\/][^>]*[^\/>]>))\n+(<\/)/U", 
			"\${1}\${4}", 
			$fixed_uncleanhtml
		);
	
		// Separate closing Javascript brackets with newlines
		$fixed_uncleanhtml = preg_replace( "/\}[\s\t]*\}/", "}\n}", $fixed_uncleanhtml );
	
		/* FIX 'HANGING' TAGS 
	
		// Insert newlines before 'hanging' closing tags (ie. <p>\nSome text</p>\n)
		$fixed_uncleanhtml = preg_replace
		( 
			"/(\n[^<\n]*[^<\n\s\t])[\s\t]*(<\/[^>\n]+>[^\n]*\n)/U", 
			"\${1}\n\${2}", 
			$fixed_uncleanhtml
		);
		// Insert newlines after 'hanging' opening tags (ie. <p>Some text\n</p>)
		$fixed_uncleanhtml = preg_replace
		(
			"/((<[a-zA-Z]>)|(<[^\/][^>]*[^\/]>))[\s\t]*([^\s\t(<\/)\n][^(<\/)\n]*\n)/", 
			"\${1}\n\${4}", 
			$fixed_uncleanhtml
		);
	
		/* HANDLE THE NO_LINEBREAK LIST 
	
		// Remove newlines after opening tags from our no_linebreak list (unless they are self-closing)
		$fixed_uncleanhtml = preg_replace
		(
			"/(<(" . implode( '|', $no_linebreak ) . ")((\s*>)|(\s[^>]*[^\/]>)))\n+/U", 
			"\${1}", 
			$fixed_uncleanhtml
		);
		// Remove newlines before closing tags from our no_linebreak list
		$fixed_uncleanhtml = preg_replace
		(
			"/\n+(<\/(" . implode( '|', $no_linebreak ) . ")[\s\t]*>)/U", "\${1}", 
			$fixed_uncleanhtml
		);
	*/
	
		/* OK, READY TO INDENT */
	
		$uncleanhtml_array = explode( "\n", $fixed_uncleanhtml );
	
		// Array to hold temporary matches from preg_match_all
		// Needed just so we can count the number of matches
		$matcharr = array();
	
		// Sets no indentation
		$indentlevel = 0;
		foreach( $uncleanhtml_array as $uncleanhtml_key=>$currentuncleanhtml )
		{
			$replaceindent = "";
	
			// Sets the indentation from current indentlevel
			for ($o = 0; $o < $indentlevel; $o++)
			{
				$replaceindent .= $indent;
			}
			
			// Toby: $noindents was causing an annoying variable not defined error. So I just defined it as NULL.
			$noindents = NULL;
			$standalones = NULL;
			
			// If self-closing tag, simply apply indent
			if( preg_match("/<([^>]+)\/>/", $currentuncleanhtml) && !preg_match("/<([^>]+)[^\/]>/", $currentuncleanhtml) )
			{
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			// If doctype declaration, simply apply indent
			else if (preg_match("/<!(.*)>/", $currentuncleanhtml))
			{
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			// If opening AND closing tag on same line, simply apply indent
			else if (preg_match("/<[^\/](.*)>/", $currentuncleanhtml) && preg_match("/<\/(.*)>/", $currentuncleanhtml))
			{
				$cleanhtml_array[$uncleanhtml_key] = $replaceindent.$currentuncleanhtml;
			}
			
			// If closing HTML tag AND not a tag from the no_indent list, or a closing JavaScript bracket (with no opening 
			// bracket on the same line), decrease indentation and then apply the new level PLUS: If there are more than one 
			// closing tag on the same line, and the number of no_indent tags is LESS than the number of closing tags, 
			// decrease indentation and then apply the new level
			// TODO: Avoid the nasty hack at the end (it's there because the variables may not have been declared)
			else if
			(
				(
					( $closes = preg_match("/<\/([^>]*)>/", $currentuncleanhtml) )
					&& !
					(
						$noindents = preg_match( "/<\/(" . implode('|', $no_indent) . ")((>)|(\s.*>))/", $currentuncleanhtml ) 
					)
				)
				|| preg_match( "/^\}{1}[^\{]*$/", $currentuncleanhtml ) 
				|| self::html_tidy_safe_a_greater_than_b(@$closes, @$noindents)
			)
			{
				$indentlevel--;
				$replaceindent = "";
				for( $o = 0; $o < $indentlevel; $o++ )
				{
					$replaceindent .= $indent;
				}
	
				$cleanhtml_array[ $uncleanhtml_key ] = $replaceindent . $currentuncleanhtml;
			}
	
			// If opening HTML tag AND not a stand-alone tag AND not a tag from the no_indent list, or opening JavaScript bracket (with no closing bracket first), increase indentation and then apply new level
			// PLUS: If there are more than one opening tag on the same line, and the number of stand-alone tags and no_indent tags are LESS than the number of opening tags, increase indentation and then apply new level
			// TODO: Avoid the nasty hack at the end (it's there because the variables may not have been declared)
	
			else if
			(
				(
					( $opens = preg_match_all( "/<[^\/]([^>]*)>/", $currentuncleanhtml, $matcharr ) ) 
					&& !
					( $standalones = preg_match_all( "/<(link|meta|base|br|img|hr)([^>]*)>/", $currentuncleanhtml, $matcharr ) )
					&& !
					( $noindents = preg_match_all("/<(" . implode('|', $no_indent) . ")((>)|(\s.*>))/", $currentuncleanhtml, $matcharr) )
				)
				|| preg_match( "/^[^\{\}]*\{[^\}]*$/", $currentuncleanhtml ) 
				|| self::html_tidy_safe_a_greater_than_b_plus_c( @$opens, @$standalones, @$noindents )
			)
			{
				$cleanhtml_array[ $uncleanhtml_key ] = $replaceindent . $currentuncleanhtml;
	
				$indentlevel++;
				$replaceindent = "";
				for( $o = 0; $o < $indentlevel; $o++ )
				{
					$replaceindent .= $indent;
				}
			}
			// If both a closing and an opening JavaScript bracket (like in a condensed else clause), decrease indentation on this line only
			else if( preg_match("/^[^\{\}]*\}[^\{\}]*\{[^\{\}]*$/", $currentuncleanhtml) )
			{
				$indentlevel--;
				$replaceindent = "";
				for( $o = 0; $o < $indentlevel; $o++ )
				{
					$replaceindent .= $indent;
				}
	
				$cleanhtml_array[ $uncleanhtml_key ] = $replaceindent . $currentuncleanhtml;
	
				// Reset indent to previous level
				$indentlevel++;
				$replaceindent .= $indent;
			}
			else
			// Else, only apply indentation
			{
				$cleanhtml_array[ $uncleanhtml_key ] = $replaceindent . $currentuncleanhtml;
			}
		}
	
		// Return single string separated by newline
		return implode( "\n", $cleanhtml_array );
	}
	
	private static function html_tidy_safe_a_greater_than_b_plus_c( $a, $b, $c )
	{
		if( !isset($a) ) $a = 0;
		if( !isset($b) ) $b = 0;
		if( !isset($c) ) $c = 0;
		return $a > ($b+$c);
	}
	
	private static function html_tidy_safe_a_greater_than_b( $a, $b )
	{
		if( !isset($a) ) $a = 0;
		if( !isset($b) ) $b = 0;
		return $a > $b;
	}
}